<?php

namespace App\Services;

use App\Models\MatterRequest;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhatsAppService
{
    /**
     * إرسال إشعار بطلب جديد
     * @throws ConnectionException
     */
    public static function notifyNewRequest(User $user, MatterRequest $matterRequest): ?Response
    {
        // تصحيح: استخدام self:: بدلاً من $this لأن الدالة static
        $formattedPhone = self::formatWhatsAppNumber($user->phone);


        if (is_null($formattedPhone)) {
            Log::error('Failed to format WhatsApp number for user: ' . $user->id);
            return null;
        }

        $response = Http::withToken(config('services.whatsapp.token'))
            ->post('https://graph.facebook.com/v19.0/' . config('services.whatsapp.phone_id') . '/messages', [
                'messaging_product' => 'whatsapp',
                'to' => $formattedPhone, // استخدام الرقم المنسق والمحقق
                'type' => 'template',
                'template' => [
                    'name' => 'new_request_notification',
                    'language' => ['code' => 'ar_AE'],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => [
                                ['type' => 'text', 'parameter_name' => 'name', 'text' => (string)$user->display_name],
                                ['type' => 'text', 'parameter_name' => 'request_number', 'text' => (string)$matterRequest->id],
                                ['type' => 'text', 'parameter_name' => 'matter_number', 'text' => (string)$matterRequest->matter->number . '/' . $matterRequest->matter->year],
                                ['type' => 'text', 'parameter_name' => 'request_type', 'text' => (string)$matterRequest->type->getLabel()],
                                ['type' => 'text', 'parameter_name' => 'request_date', 'text' => (string)$matterRequest->created_at->format('Y-m-d')],
                                ['type' => 'text', 'parameter_name' => 'request_status', 'text' => (string)$matterRequest->status->getLabel()],
                            ]
                        ],
                        [
                            'type' => 'button',
                            'sub_type' => 'url',
                            'index' => 0,
                            'parameters' => [
                                ['type' => 'text', 'parameter_name' => 'request_number', 'text' => (string)$matterRequest->id]
                            ]
                        ]
                    ]
                ],
            ]);
        if ($response->failed()) {
            \Log::error('WhatsApp Error:', $response->json());
            // سيعطيك هذا تفاصيل أكثر في ملف storage/logs/laravel.log
        }
        return $response;
    }


    public static function formatWhatsAppNumber($number): ?string
    {
        if (is_array($number)) {
            $number = $number[0];
        }
        if (empty($number) || is_array($number)) return null;

        $cleanNumber = preg_replace('/[^0-9]/', '', (string)$number);

        if (str_starts_with($cleanNumber, '009715')) {
            $cleanNumber = substr($cleanNumber, 2);
        } elseif (str_starts_with($cleanNumber, '05')) {
            $cleanNumber = '971' . substr($cleanNumber, 1);
        } elseif (str_starts_with($cleanNumber, '97105')) {
            $cleanNumber = '9715' . substr($cleanNumber, 5);
        }
        if (preg_match('/^9715[0-9]{8}$/', $cleanNumber)) {
            return $cleanNumber;
        }

        return null;
    }
}
