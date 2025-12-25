<?php

namespace App\Console\Commands;

use App\Models\Ad;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class TranscodeVideoToHLS extends Command
{
    protected $signature = 'video:transcode-hls 
                            {ad_id : The ad ID to transcode}
                            {video_path : Path to the video file}';

    protected $description = 'Transcode video file to HLS format with TS segments';

    public function handle()
    {
        $adId = $this->argument('ad_id');
        $videoPath = $this->argument('video_path');

        $ad = Ad::find($adId);
        if (!$ad) {
            $this->error("Ad with ID {$adId} not found");
            return 1;
        }

        // Check if video file exists
        $fullVideoPath = Storage::disk('public')->path($videoPath);
        if (!file_exists($fullVideoPath)) {
            $this->error("Video file not found: {$fullVideoPath}");
            return 1;
        }

        // Check if FFmpeg is available
        $ffmpegPath = $this->getFFmpegPath();
        if (!$ffmpegPath) {
            $this->error("FFmpeg is not installed. Please install FFmpeg first.");
            $this->info("Install: sudo apt-get install ffmpeg");
            return 1;
        }

        // Setup HLS output directory
        $hlsOutputDir = "ads/hls/{$adId}";
        $hlsOutputPath = Storage::disk('public')->path($hlsOutputDir);
        
        // Create directory if not exists
        if (!is_dir($hlsOutputPath)) {
            mkdir($hlsOutputPath, 0755, true);
        }

        $hlsManifestPath = "{$hlsOutputPath}/video.m3u8";
        $segmentPattern = "{$hlsOutputPath}/segment_%03d.ts";

        $this->info("Starting HLS transcoding for Ad ID: {$adId}");
        $this->info("Input: {$fullVideoPath}");
        $this->info("Output: {$hlsManifestPath}");

        // FFmpeg command to convert to HLS
        $command = sprintf(
            '%s -i %s ' .
            '-c:v libx264 -c:a aac ' .
            '-hls_time 6 ' .
            '-hls_list_size 0 ' .
            '-hls_segment_filename %s ' .
            '-hls_flags delete_segments ' .
            '-start_number 0 ' .
            '-b:v 2000k -b:a 128k ' .
            '-preset medium ' .
            '-f hls %s 2>&1',
            escapeshellarg($ffmpegPath),
            escapeshellarg($fullVideoPath),
            escapeshellarg($segmentPattern),
            escapeshellarg($hlsManifestPath)
        );

        $this->info("Running FFmpeg command...");
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $error = implode("\n", $output);
            $this->error("FFmpeg transcoding failed:");
            $this->error($error);
            Log::error("HLS transcoding failed for Ad {$adId}", [
                'command' => $command,
                'output' => $output,
                'return_code' => $returnCode
            ]);
            return 1;
        }

        // Update ad with HLS manifest URL
        $hlsManifestUrl = Storage::disk('public')->url("{$hlsOutputDir}/video.m3u8");
        
        // Regenerate VAST with HLS URL
        $vastGenerator = app(\App\Services\VASTGeneratorService::class);
        $vastXml = $vastGenerator->generateVAST($ad, $hlsManifestUrl, $ad->click_through_url);
        $vastUrl = $vastGenerator->saveVASTFile($ad, $vastXml);
        
        $ad->update([
            'vast_url' => $vastUrl,
        ]);

        $this->info("✅ HLS transcoding completed successfully!");
        $this->info("HLS Manifest: {$hlsManifestUrl}");
        $this->info("VAST URL: {$vastUrl}");

        return 0;
    }

    protected function getFFmpegPath()
    {
        // Try common FFmpeg paths
        $paths = [
            '/usr/bin/ffmpeg',
            '/usr/local/bin/ffmpeg',
            'ffmpeg', // In PATH
        ];

        foreach ($paths as $path) {
            $output = [];
            $returnCode = 0;
            exec("which {$path} 2>/dev/null", $output, $returnCode);
            if ($returnCode === 0 && !empty($output)) {
                return trim($output[0]);
            }
        }

        return null;
    }
}

