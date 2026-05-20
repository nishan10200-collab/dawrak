<?php

namespace App\Http\Controllers\Barber;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ServiceController extends Controller
{
    // قائمة الخدمات
    public function index(Request $request)
    {
        $barber = $request->user('barber');

        $services = Service::where('barber_id', $barber->id)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('category');

        return response()->json([
            'success' => true,
            'data' => $services->map(fn($items) => $items->map(fn($s) => $this->formatService($s))),
        ]);
    }

    // إضافة خدمة
    public function store(Request $request)
    {
        $barber = $request->user('barber');

        $data = $request->validate([
            'category' => 'required|in:hair,beard,special,offer',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'duration_minutes' => 'required|integer|min:5|max:180',
            'is_available' => 'boolean',
            'image' => 'nullable|image|max:2048',
        ]);

        // رفع الصورة
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('services', 'public');
        }

        $data['barber_id'] = $barber->id;
        $data['sort_order'] = Service::where('barber_id', $barber->id)->max('sort_order') + 1;

        $service = Service::create($data);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة الخدمة بنجاح',
            'data' => $this->formatService($service),
        ], 201);
    }

    // تعديل خدمة
    public function update(Request $request, Service $service)
    {
        $barber = $request->user('barber');

        if ($service->barber_id !== $barber->id) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        $data = $request->validate([
            'category' => 'in:hair,beard,special,offer',
            'name' => 'string|max:100',
            'description' => 'nullable|string|max:500',
            'price' => 'numeric|min:0',
            'duration_minutes' => 'integer|min:5|max:180',
            'is_available' => 'boolean',
            'image' => 'nullable|image|max:2048',
        ]);

        // رفع صورة جديدة وحذف القديمة
        if ($request->hasFile('image')) {
            if ($service->image) {
                Storage::disk('public')->delete($service->image);
            }
            $data['image'] = $request->file('image')->store('services', 'public');
        }

        $service->update($data);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الخدمة بنجاح',
            'data' => $this->formatService($service->fresh()),
        ]);
    }

    // حذف خدمة
    public function destroy(Request $request, Service $service)
    {
        $barber = $request->user('barber');

        if ($service->barber_id !== $barber->id) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        if ($service->image) {
            Storage::disk('public')->delete($service->image);
        }

        $service->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الخدمة بنجاح',
        ]);
    }

    // تنسيق بيانات الخدمة
    private function formatService(Service $service): array
    {
        return [
            'id' => $service->id,
            'category' => $service->category,
            'name' => $service->name,
            'description' => $service->description,
            'price' => (float) $service->price,
            'image' => $service->image ? asset('storage/' . $service->image) : null,
            'duration_minutes' => $service->duration_minutes,
            'is_available' => $service->is_available,
            'sort_order' => $service->sort_order,
        ];
    }
}
