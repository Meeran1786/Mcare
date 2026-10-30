<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;
use Throwable;

class FaqController extends Controller
{
    private array $supportedLanguages = ['en', 'te', 'hi', 'ur'];

    public function index(Request $request, string $type)
    {
        if (!in_array($type, ['help', 'payment'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'FAQ type not found',
            ], 404);
        }

        $language = strtolower((string) (
            $request->query('language')
            ?: $request->header('X-App-Language')
            ?: 'en'
        ));
        $language = explode(',', $language)[0];
        $language = explode('-', $language)[0];
        $language = in_array($language, $this->supportedLanguages, true) ? $language : 'en';

        try {
            $faqs = Faq::query()
                ->where('type', $type)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (Faq $faq) => $this->localizedFaq($faq, $language));
        } catch (Throwable $error) {
            $faqs = collect($this->fallbackFaqs($type));
        }

        return response()->json([
            'success' => true,
            'data' => $faqs,
        ]);
    }

    private function localizedFaq(Faq $faq, string $language): array
    {
        $questionField = "question_{$language}";
        $answerField = "answer_{$language}";
        $fallbackUsed = $language !== 'en' && empty($faq->{$questionField}) && empty($faq->{$answerField});

        return [
            'id' => $faq->id,
            'question' => $faq->{$questionField} ?: $faq->question_en ?: $faq->question,
            'answer' => $faq->{$answerField} ?: $faq->answer_en ?: $faq->answer,
            'language' => $fallbackUsed ? 'en' : $language,
            'fallback_used' => $fallbackUsed,
        ];
    }

    private function fallbackFaqs(string $type): array
    {
        return [
            'help' => [
                [
                    'id' => 1,
                    'question' => 'What are substitutes or generics?',
                    'answer' => 'Substitutes or generics are medicines that contain the same drug salts and provide the same therapeutic effect as branded medicines.',
                ],
                [
                    'id' => 2,
                    'question' => 'How can I place an order?',
                    'answer' => 'Upload your prescription, choose your address and patient details, then submit the order request from the app.',
                ],
                [
                    'id' => 3,
                    'question' => 'How long does delivery take?',
                    'answer' => 'Delivery time depends on pharmacy confirmation and your location. You will receive updates after your order is accepted.',
                ],
                [
                    'id' => 4,
                    'question' => 'Can I cancel my order?',
                    'answer' => 'You can cancel an order before it is processed or dispatched by the pharmacy.',
                ],
            ],
            'payment' => [
                [
                    'id' => 5,
                    'question' => 'Will I have to pay now to complete my order?',
                    'answer' => 'No. Currently the app supports cash on delivery, so you can place the order and pay when the medicines are delivered.',
                ],
                [
                    'id' => 6,
                    'question' => 'How can I pay later?',
                    'answer' => 'Select cash on delivery and place your order. Payment will be collected at the time of delivery.',
                ],
                [
                    'id' => 7,
                    'question' => 'Will my delivery be delayed in case I pay late?',
                    'answer' => 'For cash on delivery orders, delivery is not delayed by online payment confirmation. Please keep the payment ready when your order arrives.',
                ],
            ],
        ][$type] ?? [];
    }
}
