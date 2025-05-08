<?php

use Illuminate\Support\Facades\Route;
use App\Models\Surat; // Import model Surat
use Barryvdh\DomPDF\Facade\Pdf; // Import facade PDF

Route::get('/', function () {
    // Redirect ke panel admin jika langsung akses root
    return redirect('/admin');
});




// Route untuk generate PDF
Route::get('/surat/{surat}/pdf', function (Surat $surat) {
    // Pastikan hanya surat yang disetujui yang bisa dicetak
    if ($surat->status !== 'disetujui') {
        abort(403, 'Surat belum disetujui atau tidak valid untuk dicetak.');
    }

    // Pastikan user yang login adalah pegawai ybs atau pimpinan
    $user = auth()->user();
    if (!$user || !($user->id === $surat->user_id || $user->isPimpinan())) {
         abort(403, 'Anda tidak memiliki izin untuk mengakses PDF ini.');
    }

    $pdf = Pdf::loadView('pdf.surat', ['surat' => $surat]);
    return $pdf->stream('surat-'.$surat->jenis_surat.'-'.$surat->id.'.pdf');
    // atau $pdf->download(...) untuk langsung download
})->name('surat.pdf')->middleware('auth'); // Pastikan hanya user terautentikasi