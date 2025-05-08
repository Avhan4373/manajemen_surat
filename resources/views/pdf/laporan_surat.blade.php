<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pengajuan Surat</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; line-height: 1.4; margin: 20px; }
        .container { width: 100%; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 16px; text-transform: uppercase; }
        .header p { margin: 2px 0; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #666; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; font-size: 10px; text-transform: uppercase;}
        td { font-size: 10px; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .badge {
            padding: 2px 6px;
            font-size: 9px;
            border-radius: 4px;
            color: white;
            text-transform: capitalize;
        }
        .badge-success { background-color: #28a745; }
        .badge-warning { background-color: #ffc107; color: #333 }
        .badge-danger { background-color: #dc3545; }
        .badge-info { background-color: #17a2b8; }
        .badge-secondary { background-color: #6c757d; }

        @page { margin: 20mm 15mm; } /* Atur margin halaman */

        .footer {
            position: fixed;
            bottom: -10mm; /* Sesuaikan dengan margin bottom @page */
            left: 0mm;
            right: 0mm;
            height: 10mm;
            text-align: center;
            font-size: 9px;
            border-top: 1px solid #ccc;
            padding-top: 5px;
        }
        .pagenum:before {
            content: "Halaman " counter(page);
        }
    </style>
</head>
<body>
    <div class="footer">
        Dicetak oleh: {{ $currentUser->name }} pada {{ now()->translatedFormat('d F Y H:i') }} <span class="pagenum" style="float:right;"></span>
    </div>

    <div class="container">
        <div class="header">
            <h1>Laporan Pengajuan Surat</h1>
            <p>Periode: {{ $tanggalMulai->translatedFormat('d F Y') }} s/d {{ $tanggalSelesai->translatedFormat('d F Y') }}</p>
            @if($currentUser->isPimpinan() && $namaPegawaiFilter)
                <p>Pegawai: {{ $namaPegawaiFilter }}</p>
            @endif
        </div>

        <table>
            <thead>
                <tr>
                    <th class="text-center" style="width:5%;">No.</th>
                    <th style="width:15%;">Tgl. Pengajuan</th>
                    @if($currentUser->isPimpinan() && !$namaPegawaiFilter || $namaPegawaiFilter === 'Semua Pegawai')
                        <th>Pegawai</th>
                    @endif
                    <th>Jenis Surat</th>
                    <th>Periode Surat</th>
                    <th>Uraian</th>
                    <th class="text-center">Status</th>
                    <th>Pimpinan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($surats as $index => $surat)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $surat->tanggal_pengajuan->format('d/m/Y H:i') }}</td>
                    @if($currentUser->isPimpinan() && !$namaPegawaiFilter || $namaPegawaiFilter === 'Semua Pegawai')
                        <td>{{ $surat->pegawai->name }}</td>
                    @endif
                    <td>
                        @if($surat->jenis_surat == 'izin')
                            <span class="badge badge-warning">Izin</span>
                        @elseif($surat->jenis_surat == 'tugas')
                            <span class="badge badge-info">Tugas</span>
                        @else
                            {{ ucfirst($surat->jenis_surat) }}
                        @endif
                    </td>
                    <td>{{ $surat->tanggal_mulai->format('d/m/Y') }} - {{ $surat->tanggal_selesai->format('d/m/Y') }}</td>
                    <td>{{ Str::limit($surat->uraian, 50) }}</td>
                    <td class="text-center">
                        @if($surat->status == 'disetujui')
                            <span class="badge badge-success">Disetujui</span>
                        @elseif($surat->status == 'ditolak')
                            <span class="badge badge-danger">Ditolak</span>
                        @else
                            <span class="badge badge-secondary">{{ str_replace('_', ' ', ucfirst($surat->status)) }}</span>
                        @endif
                    </td>
                    <td>{{ $surat->pimpinan?->name ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ ($currentUser->isPimpinan() && !$namaPegawaiFilter || $namaPegawaiFilter === 'Semua Pegawai') ? '8' : '7' }}" class="text-center">Tidak ada data surat untuk periode ini.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div style="margin-top: 20px;">
            <p>Total Surat Diajukan: {{ $surats->count() }}</p>
            <p>Total Surat Disetujui: {{ $surats->where('status', 'disetujui')->count() }}</p>
            <p>Total Surat Ditolak: {{ $surats->where('status', 'ditolak')->count() }}</p>
            <p>Total Surat Menunggu Persetujuan: {{ $surats->where('status', 'menunggu_persetujuan')->count() }}</p>
        </div>

    </div>
</body>
</html>