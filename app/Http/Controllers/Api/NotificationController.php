<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use App\Models\NotificationLog;
use App\Models\SmsGateway;
use App\Services\Notification\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected function getNotificationService(): NotificationService
    {
        return new NotificationService(app('current_hospital_id') ?? auth()->user()->hospital_id);
    }

    // SMS Gateways
    public function gateways()
    {
        $gateways = SmsGateway::orderBy('is_default', 'desc')
            ->orderBy('gateway_name')
            ->get();

        return response()->json(['gateways' => $gateways]);
    }

    public function storeGateway(Request $request)
    {
        $validated = $request->validate([
            'gateway_name' => 'required|string|max:100',
            'provider' => 'required|in:msg91,twilio,textlocal,custom',
            'api_url' => 'nullable|url',
            'api_key' => 'required|string',
            'api_secret' => 'nullable|string',
            'sender_id' => 'required|string|max:20',
            'is_default' => 'nullable|boolean',
        ]);

        if ($validated['is_default'] ?? false) {
            SmsGateway::query()->update(['is_default' => false]);
        }

        $gateway = SmsGateway::create($validated);

        return response()->json([
            'message' => 'SMS gateway created successfully',
            'gateway' => $gateway,
        ], 201);
    }

    public function updateGateway(Request $request, SmsGateway $gateway)
    {
        $validated = $request->validate([
            'gateway_name' => 'sometimes|string|max:100',
            'api_url' => 'nullable|url',
            'api_key' => 'sometimes|string',
            'api_secret' => 'nullable|string',
            'sender_id' => 'sometimes|string|max:20',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validated['is_default'] ?? false) {
            SmsGateway::where('gateway_id', '!=', $gateway->gateway_id)
                ->update(['is_default' => false]);
        }

        $gateway->update($validated);

        return response()->json([
            'message' => 'SMS gateway updated successfully',
            'gateway' => $gateway,
        ]);
    }

    // Templates
    public function templates(Request $request)
    {
        $query = NotificationTemplate::query();

        if ($request->notification_type) {
            $query->where('notification_type', $request->notification_type);
        }

        if ($request->channel) {
            $query->where('channel', $request->channel);
        }

        $templates = $query->orderBy('template_code')->get();

        return response()->json(['templates' => $templates]);
    }

    public function storeTemplate(Request $request)
    {
        $validated = $request->validate([
            'template_code' => 'required|string|max:50',
            'notification_type' => 'required|string|max:50',
            'channel' => 'required|in:sms,email,both',
            'sms_template' => 'required_if:channel,sms,both|nullable|string',
            'sms_dlt_template_id' => 'nullable|string|max:50',
            'email_subject' => 'required_if:channel,email,both|nullable|string|max:200',
            'email_template' => 'required_if:channel,email,both|nullable|string',
            'variables' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        $template = NotificationTemplate::create($validated);

        return response()->json([
            'message' => 'Template created successfully',
            'template' => $template,
        ], 201);
    }

    public function updateTemplate(Request $request, NotificationTemplate $template)
    {
        $validated = $request->validate([
            'notification_type' => 'sometimes|string|max:50',
            'channel' => 'sometimes|in:sms,email,both',
            'sms_template' => 'nullable|string',
            'sms_dlt_template_id' => 'nullable|string|max:50',
            'email_subject' => 'nullable|string|max:200',
            'email_template' => 'nullable|string',
            'variables' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        $template->update($validated);

        return response()->json([
            'message' => 'Template updated successfully',
            'template' => $template,
        ]);
    }

    // Logs
    public function logs(Request $request)
    {
        $query = NotificationLog::with('template');

        if ($request->channel) {
            $query->where('channel', $request->channel);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->from_date && $request->to_date) {
            $query->whereBetween('created_at', [$request->from_date, $request->to_date]);
        }

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('recipient_name', 'like', "%{$search}%")
                    ->orWhere('recipient_contact', 'like', "%{$search}%");
            });
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 50);

        return response()->json($logs);
    }

    // Send notification
    public function send(Request $request)
    {
        $validated = $request->validate([
            'template_code' => 'required|string',
            'recipient_name' => 'required|string',
            'recipient_mobile' => 'required_without:recipient_email|nullable|string',
            'recipient_email' => 'required_without:recipient_mobile|nullable|email',
            'variables' => 'nullable|array',
        ]);

        $template = NotificationTemplate::where('template_code', $validated['template_code'])
            ->where('is_active', true)
            ->firstOrFail();

        $this->getNotificationService()->sendFromTemplate(
            app('current_hospital_id') ?? auth()->user()->hospital_id,
            $template,
            $validated['recipient_name'],
            $validated['recipient_mobile'] ?? '',
            $validated['recipient_email'] ?? null,
            $validated['variables'] ?? []
        );

        return response()->json([
            'message' => 'Notification queued successfully',
        ]);
    }

    // Test SMS gateway
    public function testGateway(Request $request, SmsGateway $gateway)
    {
        $validated = $request->validate([
            'mobile' => 'required|string',
            'message' => 'required|string',
        ]);

        $result = $this->getNotificationService()->testGateway(
            $gateway,
            $validated['mobile'],
            $validated['message']
        );

        return response()->json($result);
    }
}
