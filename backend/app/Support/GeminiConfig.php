<?php

namespace App\Support;

final class GeminiConfig
{
    private const API_KEY = 'AIzaSyDWNfAVDXZgE1Z_RBQiRRmiarXVFIR_m_Q';
    private const DEFAULT_MODEL = 'gemini-1.5-flash';
    private const DEFAULT_CHAT_MODEL = 'gemini-2.0-flash';
    private const SUMMARY_MODELS = [
        'gemini-1.5-flash',
        'gemini-1.5-flash-latest',
        'gemini-1.5-flash-001',
        'gemini-1.5-pro',
        'gemini-1.5-pro-latest',
    ];

    public static function apiKey(): string
    {
        return self::API_KEY;
    }

    public static function defaultModel(): string
    {
        return self::DEFAULT_MODEL;
    }

    public static function chatModel(): string
    {
        return self::DEFAULT_CHAT_MODEL;
    }

    public static function summaryModels(): array
    {
        return self::SUMMARY_MODELS;
    }
}
