<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\AdRule;
use App\Models\Channel;
use Illuminate\Http\Request;

class AdRuleController extends Controller
{
    public function store(Request $request, Ad $ad)
    {
        // Handle checkbox input (channels[]) or direct rule_value
        $ruleValue = null;
        $ruleOperator = $request->input('rule_operator', 'equals');
        
        if ($request->has('channels') && is_array($request->input('channels'))) {
            // Checkbox input: channels[] array
            $selectedChannels = $request->input('channels');
            if (count($selectedChannels) > 1) {
                $ruleOperator = 'in';
                $ruleValue = json_encode($selectedChannels);
            } else {
                $ruleOperator = 'equals';
                $ruleValue = $selectedChannels[0];
            }
        } else {
            // Direct rule_value input (from hidden field)
            $ruleValue = $request->input('rule_value');
            $ruleOperator = $request->input('rule_operator', 'equals');
        }

        $validated = [
            'rule_type' => $request->input('rule_type', 'channel'),
            'rule_operator' => $ruleOperator,
            'rule_value' => $ruleValue,
            'priority' => $request->input('priority', 0),
            'ad_id' => $ad->id,
        ];

        // Validate
        $request->validate([
            'rule_type' => 'required|in:channel,geo,device,time,day_of_week',
            'priority' => 'nullable|integer|min:0',
        ]);

        // If rule_type is 'channel' and rule_operator is 'in', ensure rule_value is JSON array
        if ($validated['rule_type'] === 'channel' && $validated['rule_operator'] === 'in') {
            // If rule_value is comma-separated, convert to JSON array
            if (strpos($validated['rule_value'], ',') !== false && !str_starts_with(trim($validated['rule_value']), '[')) {
                $channels = array_map('trim', explode(',', $validated['rule_value']));
                $validated['rule_value'] = json_encode($channels);
            } elseif (!str_starts_with(trim($validated['rule_value']), '[')) {
                // Single value but operator is 'in', wrap in array
                $validated['rule_value'] = json_encode([trim($validated['rule_value'])]);
            }
        } elseif ($validated['rule_type'] === 'channel' && $validated['rule_operator'] === 'equals') {
            // Single channel, just trim
            $validated['rule_value'] = trim($validated['rule_value']);
        }

        AdRule::create($validated);

        return redirect()->route('ads.show', $ad)
            ->with('success', 'Ad rule created successfully.');
    }

    public function destroy(Ad $ad, AdRule $rule)
    {
        // Verify rule belongs to ad
        if ($rule->ad_id !== $ad->id) {
            abort(403);
        }

        $rule->delete();

        return redirect()->route('ads.show', $ad)
            ->with('success', 'Ad rule deleted successfully.');
    }
}

