<?php

namespace App\Services;

use App\Models\Ad;

class VASTGeneratorService
{
    /**
     * Generate VAST XML for uploaded video
     */
    public function generateVAST(Ad $ad, string $videoUrl, ?string $clickThroughUrl = null): string
    {
        $duration = $this->formatDuration($ad->duration_seconds);
        $baseUrl = config('app.url', 'http://localhost:8000');
        
        $vast = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $vast .= '<VAST version="3.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">' . "\n";
        $vast .= '  <Ad id="' . htmlspecialchars($ad->id) . '">' . "\n";
        $vast .= '    <InLine>' . "\n";
        $vast .= '      <AdSystem version="1.0">FAST Ads Backend</AdSystem>' . "\n";
        $vast .= '      <AdTitle>' . htmlspecialchars($ad->name) . '</AdTitle>' . "\n";
        $vast .= '      <Description>' . htmlspecialchars($ad->name) . '</Description>' . "\n";
        
        // Impression tracking
        $vast .= '      <Impression><![CDATA[' . $baseUrl . '/api/v1/tracking/events?ad_id=' . $ad->id . '&event_type=impression]]></Impression>' . "\n";
        
        $vast .= '      <Creatives>' . "\n";
        $vast .= '        <Creative id="creative_' . $ad->id . '" sequence="1">' . "\n";
        $vast .= '          <Linear>' . "\n";
        $vast .= '            <Duration>' . $duration . '</Duration>' . "\n";
        
        // Tracking events
        $vast .= '            <TrackingEvents>' . "\n";
        $vast .= '              <Tracking event="start"><![CDATA[' . $baseUrl . '/api/v1/tracking/events?ad_id=' . $ad->id . '&event_type=start]]></Tracking>' . "\n";
        $vast .= '              <Tracking event="firstQuartile"><![CDATA[' . $baseUrl . '/api/v1/tracking/events?ad_id=' . $ad->id . '&event_type=first_quartile]]></Tracking>' . "\n";
        $vast .= '              <Tracking event="midpoint"><![CDATA[' . $baseUrl . '/api/v1/tracking/events?ad_id=' . $ad->id . '&event_type=midpoint]]></Tracking>' . "\n";
        $vast .= '              <Tracking event="thirdQuartile"><![CDATA[' . $baseUrl . '/api/v1/tracking/events?ad_id=' . $ad->id . '&event_type=third_quartile]]></Tracking>' . "\n";
        $vast .= '              <Tracking event="complete"><![CDATA[' . $baseUrl . '/api/v1/tracking/events?ad_id=' . $ad->id . '&event_type=complete]]></Tracking>' . "\n";
        $vast .= '            </TrackingEvents>' . "\n";
        
        // Video clicks
        if ($clickThroughUrl || $ad->click_through_url) {
            $clickUrl = $clickThroughUrl ?? $ad->click_through_url;
            $vast .= '            <VideoClicks>' . "\n";
            $vast .= '              <ClickThrough><![CDATA[' . htmlspecialchars($clickUrl) . ']]></ClickThrough>' . "\n";
            $vast .= '              <ClickTracking><![CDATA[' . $baseUrl . '/api/v1/tracking/events?ad_id=' . $ad->id . '&event_type=click]]></ClickTracking>' . "\n";
            $vast .= '            </VideoClicks>' . "\n";
        }
        
        // Media files
        $vast .= '            <MediaFiles>' . "\n";
        
        // Check if URL is HLS manifest (.m3u8)
        if (str_ends_with(strtolower($videoUrl), '.m3u8')) {
            // HLS manifest
            $vast .= '              <MediaFile id="media_' . $ad->id . '_hls" delivery="streaming" type="application/x-mpegURL" bitrate="2000" width="1920" height="1080">' . "\n";
            $vast .= '                <![CDATA[' . htmlspecialchars($videoUrl) . ']]>' . "\n";
            $vast .= '              </MediaFile>' . "\n";
        } else {
            // MP4 or other formats (fallback)
            $vast .= '              <MediaFile id="media_' . $ad->id . '" delivery="progressive" type="video/mp4" bitrate="2000" width="1920" height="1080">' . "\n";
            $vast .= '                <![CDATA[' . htmlspecialchars($videoUrl) . ']]>' . "\n";
            $vast .= '              </MediaFile>' . "\n";
        }
        
        $vast .= '            </MediaFiles>' . "\n";
        
        $vast .= '          </Linear>' . "\n";
        $vast .= '        </Creative>' . "\n";
        $vast .= '      </Creatives>' . "\n";
        $vast .= '    </InLine>' . "\n";
        $vast .= '  </Ad>' . "\n";
        $vast .= '</VAST>';
        
        return $vast;
    }
    
    /**
     * Format duration from seconds to HH:MM:SS
     */
    protected function formatDuration(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }
    
    /**
     * Save VAST XML to file
     */
    public function saveVASTFile(Ad $ad, string $vastXml): string
    {
        $filename = 'vast_' . $ad->id . '_' . time() . '.xml';
        $path = 'ads/vast/' . $filename;
        
        \Storage::disk('public')->put($path, $vastXml);
        
        return \Storage::disk('public')->url($path);
    }
}

