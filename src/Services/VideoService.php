<?php

namespace Vendor\Veeroll\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Video;
use App\Models\Language;
use App\Models\TextEffect;
use App\Models\MusicLibrary;
use App\Models\VideoEffect;
use App\Http\Resources\VideoResource;

class VideoService
{
    public function expressMode(Request $request)
    {
        try {
            set_time_limit(0);
            ini_set('memory_limit', '-1');

            DB::beginTransaction();

            $language_id = Language::where('default', 1)->first()->id;
            $aspect_ratio_id = $request->picture_format_id;
            $text_effect_id = TextEffect::where('default', 1)->first()->id;
            $scene_duration = $request->duration;
            $voice_over = $request->voice_over;
            $caption = $request->captions;
            $text_position = 50;
            $text_color = null;
            $highlight_color = null;
            $music = 1;
            $font_outline_color = '#000000';
            $outline_width = 1;
            $font_id = null;
            $font_size = null;

            $company_branding = $request->user()->company->company_branding;

            if ($company_branding) {
                $text_position = $company_branding->default_text_position ?? $text_position;
                $text_color = $company_branding->default_text_color ?? $text_color;
                $highlight_color = $company_branding->default_highlight_color ?? $highlight_color;
                $font_outline_color = $company_branding->default_font_outline_color;
                $outline_width = $company_branding->default_outline_width;
                $font_id = $company_branding->default_font;
                $font_size = $company_branding->default_font_size;
                $background_color = $company_branding->default_background_color;
            }

            $textEffect = TextEffect::where('label', 'Karaoke')->first();
            $music = MusicLibrary::where('music_tone_id', $request->tone_id)
                ->where('status', 1)
                ->inRandomOrder()->first();

            $videoEffect = VideoEffect::where('label', 'Random')
                ->where('picture_format_id', $aspect_ratio_id)
                ->first();

            $request->merge([
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
                'company_id' => $request->user()->company_id,
                'duration' => $scene_duration,
                'default_text_color' => $text_color,
                'default_highlight_color' => $highlight_color,
                'default_text_position' => $text_position,
                'default_scene_duration' => 30,
                'default_font_outline_color' => $font_outline_color,
                'default_outline_width' => $outline_width,
                'language_id' => $request->language_id ?? $language_id,
                'picture_format_id' => $aspect_ratio_id,
                'is_active' => 1,
                'voice_over' => $voice_over ?? 0,
                'music' => 1,
                'captions' => $caption ?? 0,
                'font_id' => $font_id,
                'default_font_size' => $font_size,
                'default_effect_id' => $videoEffect->id,
                'default_background_color' => $background_color,
                'text_effect_id' => $text_effect_id,
                'creation_type' => 'express',
                'generating_ai_concept' => 0,
                'pwd_protected' => 0,
                'tone_id' => $request->tone_id,
                'ai_voice_id' => $request->voice,
                'use_own_audio' => 0,
                'background_color' => $background_color ?? '#000000',
                'video_tone_id' => $request->tone_id,
                'user_id' => auth('api')->user()->id,
                'company_id' => auth('api')->user()->company_id,
                'ai_style_id' => $request->ai_style_id ?? 1,
                'text_effect_id' => $textEffect->id ?? 2,
                'music_tone_id' => $request->tone_id,
                'music_url' => $music->url,
                'logo_enabled' => isset($request->logo_enabled) ? $request->logo_enabled : false,
                'logo_position' => isset($request->logo_position) ? $request->logo_position : null
            ]);

            $video = Video::create($request->all());
            $request->merge(['video_id' => $video->id]);
            $request->merge(['id' => $video->id]);

            $this->generateAIContent($request, $video->id, true);
            if ($request->voice_over) {
                $this->aiVoiceController->generateAllVoiceOvers($request);
            }

            $assetHandlers = [
                'stock_pictures' => 'generateStockImages',
                'plain' => 'expressModeSolidBackground',
                'stock_videos' => 'generateStockVideos',
                'express_mode' => 'generateAIImages',
            ];
            $assetType = $request->asset_type;
            if (isset($assetHandlers[$assetType])) {
                $method = $assetHandlers[$assetType];
                $this->$method($request, $video->id);
            }

            DB::commit();
            return response()->json([
                'data' => new VideoResource($video),
                'message' => 'Video created successfully'
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
                'message' => 'Video creation failed'
            ], 500);
        }
    }

    // Include other helper methods like generateAIContent, etc.
}
