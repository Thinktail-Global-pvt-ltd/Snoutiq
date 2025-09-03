<?php

namespace App\Http\Controllers\Api\Groomer;

use App\Http\Controllers\Controller;
use App\Models\GroomerEmployee;
use Illuminate\Http\Request;

class GroomerEmployeeController extends Controller
{
    public function get(Request $request)
    {
      
            $employees = GroomerEmployee::where('user_id', $request->user()->id)->get();
            return response()->json([
                'status' => true,
                'data' => $employees,
            ], 200);
         
    }
  public function show(Request $request, $id)
    {
        try {
            $employee = GroomerEmployee::where('user_id', $request->user()->id)
                ->where('id', $id)
                ->firstOrFail();
            return response()->json([
                'status' => true,
                'data' => $employee,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Employee not found',
            ], 404);
        }
    }
    public function store(Request $request)
    {
       
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email',
                'phone' => 'required|string|max:20',
                'dob' => 'required|date',
                'calendar_color' => 'required|string|max:7',
                'job_title' => 'required|string|max:255',
                'notes' => 'nullable|string',
                'services' => 'required|array',
                'services.*' => 'exists:groomer_services,id',
                'type' => 'required|in:salary,commission',
                'monthly_salary' => 'nullable|numeric|required_if:type,salary',
                'commissions' => 'nullable|array|required_if:type,commission',
                'commissions.*' => 'numeric|min:0|max:100',
                'address' => 'required|string',
            ]);

            $validated['user_id'] = $request->user()->id;

            $employee = GroomerEmployee::create($validated);

            return response()->json([
                'status' => true,
                'message' => 'Employee created successfully',
                'data' => $employee,
            ], 201);
      
    }
      public function update(Request $request, $id)
    {
      
            $employee = GroomerEmployee::where('user_id', $request->user()->id)
                ->where('id', $id)
                ->firstOrFail();

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email',
                'phone' => 'required|string|max:20',
                'dob' => 'required|date',
                'calendar_color' => 'required|string|max:7',
                'job_title' => 'required|string|max:255',
                'notes' => 'nullable|string',
                'services' => 'required|array',
                'services.*' => 'exists:groomer_services,id',
                'type' => 'required|in:salary,commission',
                'monthly_salary' => 'nullable|numeric|required_if:type,salary',
                'commissions' => 'nullable|array|required_if:type,commission',
                'commissions.*' => 'numeric|min:0|max:100',
                'address' => 'required|string',
            ]);

            $employee->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Employee updated successfully',
                'data' => $employee,
            ], 200);
       
    }
}