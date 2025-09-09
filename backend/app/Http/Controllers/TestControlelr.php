<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\DB;
use Exception;

class TestControlelr extends Controller
{
    public function importPdfData()
    {
        try {
            // ✅ Path from public/pdf
            $pdfPath = public_path('pdf/List-of-Valid-Registered-Veterinary-Practitioners-updated-till-28.10.2024.pdf');

            // Debugging check
            if (!file_exists($pdfPath)) {
                return response()->json([
                    'success' => false,
                    'message' => "❌ File not found at: " . $pdfPath,
                ], 404);
            }

            // Parse PDF
            $pdf = new Parser();
            $pdfData = $pdf->parseFile($pdfPath);
            $text = $pdfData->getText();

            $lines = preg_split('/\r\n|\r|\n/', $text);
            $inserted = 0;

            foreach ($lines as $line) {
                if (preg_match('/^(\d+)\s+(.*?)\s+(Sh\.|W\/o|D\/o|Lt\.|Late|Capt\.|Col\.|Dr\.)?(.*?)\s+(HVC\s*\d+)/', $line, $m)) {
                    DB::table('veterinary_practitioners')->insert([
                        'serial_no' => $m[1],
                        'full_name' => trim($m[2]),
                        'father_or_husband_name' => trim($m[4]),
                        'registration_no' => $m[5],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $inserted++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "✅ $inserted records imported successfully."
            ], 200);

        } catch (Exception $e) {
            // Agar koi error aaya
            return response()->json([
                'success' => false,
                'message' => '❌ Error while importing data',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}

