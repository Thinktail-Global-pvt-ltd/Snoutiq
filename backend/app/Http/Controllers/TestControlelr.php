<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\DB;

class TestControlelr extends Controller
{
    public function importPdfData()
    {
        // ✅ Path from public/pdf
        $pdfPath = public_path('pdf/List-of-Valid-Registered-Veterinary-Practitioners-updated-till-28.10.2024.pdf');

        // Debugging check
        if (!file_exists($pdfPath)) {
            return "❌ File not found at: " . $pdfPath;
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

        return "✅ $inserted records imported successfully.";
    }
}

