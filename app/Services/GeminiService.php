<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class GeminiService
{
    private $apiKey;
    private $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key', env('GEMINI_API_KEY'));
        $this->baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';
        
        if (empty($this->apiKey)) {
            \Log::error('GEMINI_API_KEY is empty or not found! Please check your .env configuration.');
            throw new \Exception('Gemini API key is not configured properly.');
        }
    }

    /**
     * Get HTTP client with appropriate SSL configuration
     */
    private function getHttpClient()
    {
        $client = Http::timeout(30);
        
        // For development environments, disable SSL verification
        if (app()->environment(['local', 'development'])) {
            $client = $client->withOptions([
                'verify' => false,
                'curl' => [
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                ],
            ]);
        }
        
        return $client;
    }

    public function generateResponse(string $prompt, array $context = []): string
    {
        try {
            // Build conversation context with beauty system prompt
            $conversationHistory = $this->buildConversationHistory($context);
            $systemPrompt = $this->getBeautySystemPrompt();
            
            // Prepare the request payload
            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $systemPrompt . $conversationHistory . $prompt
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => 1024,
                ]
            ];

            // Make the API request with SSL options for development
            $response = $this->getHttpClient()
                ->post($this->baseUrl . '/gemini-2.0-flash:generateContent?key=' . $this->apiKey, $payload);

            if (!$response->successful()) {
                throw new Exception('API request failed: ' . $response->body());
            }

            $responseData = $response->json();
            
            if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                return $responseData['candidates'][0]['content']['parts'][0]['text'];
            }
            
            throw new Exception('Invalid response format from Gemini API');
            
        } catch (Exception $e) {
            // Log the error for debugging
            \Log::error('Gemini API Error (generateResponse): ' . $e->getMessage());
            
            // Check if it's an SSL-related error
            if (str_contains($e->getMessage(), 'SSL certificate') || 
                str_contains($e->getMessage(), 'cURL error 60')) {
                throw new Exception('SSL certificate issue. Please check your SSL configuration or contact support.');
            }
            
            throw new Exception('Failed to generate response: ' . $e->getMessage());
        }
    }

    private function buildConversationHistory(array $context): string
    {
        $history = '';
        foreach ($context as $message) {
            $role = $message['role'] === 'assistant' ? 'Model' : 'User';
            $history .= "{$role}: {$message['content']}\n";
        }
        return $history ? $history . "\nUser: " : '';
    }

    // Alternative method for more complex conversations
    public function generateResponseWithHistory(string $prompt, array $context = []): string
    {
        try {
            // Build contents array for proper conversation handling
            $contents = [];
            
            // Add system prompt as the first message
            $contents[] = [
                'role' => 'user',
                'parts' => [
                    ['text' => $this->getBeautySystemPrompt()]
                ]
            ];
            
            $contents[] = [
                'role' => 'model',
                'parts' => [
                    ['text' => 'I understand! I\'m your dedicated beauty and skincare assistant. I\'m here to help you with makeup tips, skincare routines, product recommendations, and beauty advice. What would you like to know about beauty and skincare?']
                ]
            ];
            
            foreach ($context as $message) {
                $contents[] = [
                    'role' => $message['role'] === 'assistant' ? 'model' : 'user',
                    'parts' => [
                        ['text' => $message['content']]
                    ]
                ];
            }

            // Add the current user message
            $contents[] = [
                'role' => 'user',
                'parts' => [
                    ['text' => $prompt]
                ]
            ];

            $payload = [
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => 0.7,
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => 1024,
                ]
            ];

            $response = $this->getHttpClient()
                ->post($this->baseUrl . '/gemini-2.0-flash:generateContent?key=' . $this->apiKey, $payload);

            if (!$response->successful()) {
                throw new Exception('API request failed: ' . $response->body());
            }

            $responseData = $response->json();
            
            if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                return $responseData['candidates'][0]['content']['parts'][0]['text'];
            }
            
            throw new Exception('Invalid response format from Gemini API');
            
        } catch (Exception $e) {
            // Log the error for debugging
            \Log::error('Gemini API Error (generateResponseWithHistory): ' . $e->getMessage());
            
            // Check if it's an SSL-related error
            if (str_contains($e->getMessage(), 'SSL certificate') || 
                str_contains($e->getMessage(), 'cURL error 60')) {
                throw new Exception('SSL certificate issue. Please check your SSL configuration or contact support.');
            }
            
            throw new Exception('Failed to generate response: ' . $e->getMessage());
        }
    }

    /**
     * Get the beauty and skincare focused system prompt
     */
    private function getBeautySystemPrompt(): string
    {
        return "You are a professional beauty and skincare consultant with extensive knowledge in makeup, skincare, beauty treatments, and cosmetics. Your expertise includes:

🌟 **Makeup & Cosmetics:**
- Foundation matching and application techniques
- Eye makeup (eyeshadow, eyeliner, mascara, brows)
- Lip products and color coordination
- Contouring and highlighting
- Color theory and makeup for different occasions
- Product recommendations for various budgets
- Makeup tools and brushes

💆‍♀️ **Skincare & Treatments:**
- Skincare routines for all skin types (dry, oily, combination, sensitive)
- Anti-aging treatments and ingredients
- Acne treatment and prevention
- Product ingredients analysis (retinoids, AHA/BHA, vitamin C, niacinamide, etc.)
- Seasonal skincare adjustments
- Professional treatments (facials, chemical peels, microdermabrasion)
- Natural and DIY skincare remedies

✨ **Beauty Advice:**
- Skin type identification and assessment
- Product layering and application order
- Beauty routine timing (morning vs evening)
- Makeup and skincare for different ages
- Beauty on a budget vs luxury options
- Cruelty-free and clean beauty alternatives
- Beauty tools and accessories

**Your Approach:**
- Always ask about skin type, concerns, and current routine when giving skincare advice
- Provide personalized recommendations based on individual needs
- Suggest products across different price ranges
- Include application tips and techniques
- Mention potential skin reactions or patch testing when relevant
- Stay updated with current beauty trends while focusing on what works
- Be inclusive and considerate of all skin tones and types

**Important Guidelines:**
- Always recommend patch testing new products
- Suggest consulting a dermatologist for serious skin concerns
- Focus only on beauty, makeup, and skincare topics
- If asked about non-beauty topics, politely redirect to beauty and skincare
- Provide step-by-step instructions when explaining techniques
- Include emoji and formatting to make responses engaging and easy to read

Please respond only to beauty, makeup, and skincare related questions. If someone asks about other topics, kindly redirect them back to beauty and skincare discussions.

";
    }
}