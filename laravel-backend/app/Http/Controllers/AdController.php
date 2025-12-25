<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\Tenant;
use App\Models\AdCampaign;
use App\Models\Channel;
use App\Services\VASTGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdController extends Controller
{
    protected $vastGenerator;

    public function __construct(VASTGeneratorService $vastGenerator)
    {
        $this->vastGenerator = $vastGenerator;
    }

    public function index()
    {
        $ads = Ad::with(['tenant', 'campaign'])->latest()->paginate(15);
        return view('ads.index', compact('ads'));
    }

    public function create()
    {
        $tenants = Tenant::where('status', 'active')->get();
        $campaigns = AdCampaign::where('status', 'active')->get();
        return view('ads.create', compact('tenants', 'campaigns'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'campaign_id' => 'nullable|exists:ad_campaigns,id',
            'name' => 'required|string|max:255',
            'ad_source' => 'required|in:vast_url,uploaded_video',
            'vast_url' => 'required_if:ad_source,vast_url|nullable|url|max:512',
            'video_file' => 'required_if:ad_source,uploaded_video|nullable|file|mimes:mp4,webm,avi,mov|max:512000', // Max 500MB
            'duration_seconds' => 'required|integer|min:1',
            'ad_type' => 'required|in:linear,non-linear,companion',
            'click_through_url' => 'nullable|url|max:512',
            'status' => 'required|in:active,inactive',
        ]);

        $data = [
            'tenant_id' => $validated['tenant_id'],
            'campaign_id' => $validated['campaign_id'] ?? null,
            'name' => $validated['name'],
            'duration_seconds' => $validated['duration_seconds'],
            'ad_type' => $validated['ad_type'],
            'click_through_url' => $validated['click_through_url'] ?? null,
            'status' => $validated['status'],
        ];

        // Handle video upload or VAST URL
        if ($validated['ad_source'] === 'uploaded_video' && $request->hasFile('video_file')) {
            // Upload video file
            $videoFile = $request->file('video_file');
            $videoPath = $videoFile->store('ads/videos', 'public');
            $data['video_file_path'] = $videoPath;
            $data['ad_source'] = 'uploaded_video';
            
            // Create ad first to get ID
            $data['vast_url'] = 'temp'; // Temporary, will be updated after transcoding
            $ad = Ad::create($data);
            
            // Transcode video to HLS in background
            try {
                \Artisan::call('video:transcode-hls', [
                    'ad_id' => $ad->id,
                    'video_path' => $videoPath,
                ]);
                
                // Reload ad to get updated VAST URL
                $ad->refresh();
                
                return redirect()->route('ads.index')
                    ->with('success', 'Ad created successfully. Video is being transcoded to HLS.');
            } catch (\Exception $e) {
                // If transcoding fails, fallback to MP4
                \Log::error("HLS transcoding failed for Ad {$ad->id}: " . $e->getMessage());
                
                $videoUrl = Storage::disk('public')->url($videoPath);
                $vastXml = $this->vastGenerator->generateVAST(
                    $ad,
                    $videoUrl,
                    $validated['click_through_url'] ?? null
                );
                
                $vastUrl = $this->vastGenerator->saveVASTFile($ad, $vastXml);
                $ad->update(['vast_url' => $vastUrl]);
                
                return redirect()->route('ads.index')
                    ->with('warning', 'Ad created but HLS transcoding failed. Using MP4 format.');
            }
        } else {
            // Use provided VAST URL
            $data['vast_url'] = $validated['vast_url'];
            $data['ad_source'] = 'vast_url';
            $data['video_file_path'] = null;
        }

    }

    public function show(Ad $ad)
    {
        $ad->load(['tenant', 'campaign', 'rules']);
        $channels = Channel::where('tenant_id', $ad->tenant_id)->get();
        return view('ads.show', compact('ad', 'channels'));
    }

    public function edit(Ad $ad)
    {
        $tenants = Tenant::where('status', 'active')->get();
        $campaigns = AdCampaign::where('status', 'active')->get();
        
        // Ensure $errors is available for the view (ShareErrorsFromSession middleware should handle this, but ensure it's set)
        if (!isset($errors)) {
            $errors = session('errors') ?: new \Illuminate\Support\ViewErrorBag();
        }
        
        return view('ads.edit', compact('ad', 'tenants', 'campaigns', 'errors'));
    }

    public function update(Request $request, Ad $ad)
    {
        $validated = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'campaign_id' => 'nullable|exists:ad_campaigns,id',
            'name' => 'required|string|max:255',
            'ad_source' => 'required|in:vast_url,uploaded_video',
            'vast_url' => 'required_if:ad_source,vast_url|nullable|url|max:512',
            'video_file' => 'nullable|file|mimes:mp4,webm,avi,mov|max:102400',
            'duration_seconds' => 'required|integer|min:1',
            'ad_type' => 'required|in:linear,non-linear,companion',
            'click_through_url' => 'nullable|url|max:512',
            'status' => 'required|in:active,inactive',
        ]);

        $data = [
            'tenant_id' => $validated['tenant_id'],
            'campaign_id' => $validated['campaign_id'] ?? null,
            'name' => $validated['name'],
            'duration_seconds' => $validated['duration_seconds'],
            'ad_type' => $validated['ad_type'],
            'click_through_url' => $validated['click_through_url'] ?? null,
            'status' => $validated['status'],
        ];

        // Handle video upload or VAST URL
        if ($validated['ad_source'] === 'uploaded_video') {
            if ($request->hasFile('video_file')) {
                // Delete old video and HLS if exists
                if ($ad->video_file_path) {
                    Storage::disk('public')->delete($ad->video_file_path);
                    // Delete old HLS directory
                    $oldHlsDir = "ads/hls/{$ad->id}";
                    if (Storage::disk('public')->exists($oldHlsDir)) {
                        Storage::disk('public')->deleteDirectory($oldHlsDir);
                    }
                }
                
                // Upload new video
                $videoFile = $request->file('video_file');
                $videoPath = $videoFile->store('ads/videos', 'public');
                $data['video_file_path'] = $videoPath;
                
                // Transcode video to HLS
                try {
                    \Artisan::call('video:transcode-hls', [
                        'ad_id' => $ad->id,
                        'video_path' => $videoPath,
                    ]);
                    
                    // Reload ad to get updated VAST URL
                    $ad->refresh();
                } catch (\Exception $e) {
                    // If transcoding fails, fallback to MP4
                    \Log::error("HLS transcoding failed for Ad {$ad->id}: " . $e->getMessage());
                    
                    $videoUrl = Storage::disk('public')->url($videoPath);
                    $vastXml = $this->vastGenerator->generateVAST($ad, $videoUrl, $validated['click_through_url'] ?? null);
                    $vastUrl = $this->vastGenerator->saveVASTFile($ad, $vastXml);
                    $data['vast_url'] = $vastUrl;
                }
            } else {
                // Keep existing video, but regenerate VAST if needed
                if ($ad->video_file_path) {
                    // Check if HLS exists, if not, try to transcode
                    $hlsDir = "ads/hls/{$ad->id}";
                    if (!Storage::disk('public')->exists("{$hlsDir}/video.m3u8")) {
                        // Try to transcode existing video
                        try {
                            \Artisan::call('video:transcode-hls', [
                                'ad_id' => $ad->id,
                                'video_path' => $ad->video_file_path,
                            ]);
                            $ad->refresh();
                        } catch (\Exception $e) {
                            \Log::error("HLS transcoding failed for Ad {$ad->id}: " . $e->getMessage());
                            // Fallback to MP4
                            $videoUrl = Storage::disk('public')->url($ad->video_file_path);
                            $vastXml = $this->vastGenerator->generateVAST($ad, $videoUrl, $validated['click_through_url'] ?? null);
                            $vastUrl = $this->vastGenerator->saveVASTFile($ad, $vastXml);
                            $data['vast_url'] = $vastUrl;
                        }
                    }
                }
            }
            $data['ad_source'] = 'uploaded_video';
        } else {
            // Use provided VAST URL
            $data['vast_url'] = $validated['vast_url'];
            $data['ad_source'] = 'vast_url';
            // Delete video file if switching to VAST URL
            if ($ad->video_file_path) {
                Storage::disk('public')->delete($ad->video_file_path);
                $data['video_file_path'] = null;
            }
        }

        $ad->update($data);

        return redirect()->route('ads.index')
            ->with('success', 'Ad updated successfully.');
    }

    public function destroy(Ad $ad)
    {
        $ad->delete();

        return redirect()->route('ads.index')
            ->with('success', 'Ad deleted successfully.');
    }
}
