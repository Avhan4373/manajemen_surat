<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat {{ ucfirst($surat->jenis_surat) }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; line-height: 1.6; margin: 30px; }
        .container { width: 100%; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { margin: 0; font-size: 18px; text-transform: uppercase; }
        .header p { margin: 0; font-size: 14px; }
        .content table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .content th, .content td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .content th { background-color: #f2f2f2; }
        .status { font-weight: bold; }
        .status.disetujui { color: green; }
        .status.ditolak { color: red; }
        .status.menunggu_persetujuan { color: orange; }
        .footer { margin-top: 40px; }
        .signature-block { margin-top: 50px; width: 30%; float: right; text-align: center; }
        .clearfix::after { content: ""; clear: both; display: table; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Surat {{ ucfirst($surat->jenis_surat) }}</h1>
            <p>Nomor Surat: SR-{{ $surat->id }}-{{ date('Y') }} (Contoh)</p>
        </div>

        <div class="content">
            <p>Yang bertanda tangan di bawah ini:</p>
            <table>
                <tr>
                    <th style="width: 30%;">Nama Pegawai</th>
                    <td>{{ $surat->pegawai->name }}</td>
                </tr>
                <tr>
                    <th>Email Pegawai</th>
                    <td>{{ $surat->pegawai->email }}</td>
                </tr>
            </table>

            <p>Mengajukan {{ $surat->jenis_surat }} dengan rincian sebagai berikut:</p>
            <table>
                <tr>
                    <th style="width: 30%;">Jenis Surat</th>
                    <td>{{ ucfirst($surat->jenis_surat) }}</td>
                </tr>
                <tr>
                    <th>Tanggal Mulai</th>
                    <td>{{ $surat->tanggal_mulai->translatedFormat('d F Y') }}</td>
                </tr>
                <tr>
                    <th>Tanggal Selesai</th>
                    <td>{{ $surat->tanggal_selesai->translatedFormat('d F Y') }}</td>
                </tr>
                <tr>
                    <th>Uraian/Keterangan</th>
                    <td>{{ $surat->uraian }}</td>
                </tr>
                <tr>
                    <th>Tanggal Pengajuan</th>
                    <td>{{ $surat->tanggal_pengajuan->translatedFormat('d F Y H:i') }}</td>
                </tr>
            </table>

            <h4>Status Persetujuan:</h4>
            <table>
                <tr>
                    <th style="width: 30%;">Status</th>
                    <td class="status {{ $surat->status }}">
                        {{ str_replace('_', ' ', ucfirst($surat->status)) }}
                    </td>
                </tr>
                @if($surat->status !== 'menunggu_persetujuan')
                <tr>
                    <th>Ditangani Oleh</th>
                    <td>{{ $surat->pimpinan?->name ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Tanggal Keputusan</th>
                    <td>{{ $surat->updated_at->translatedFormat('d F Y H:i') }}</td>
                </tr>
                <tr>
                    <th>Catatan Pimpinan</th>
                    <td>{{ $surat->catatan_pimpinan ?? '-' }}</td>
                </tr>
                @endif
            </table>

            @if($surat->lampiran_url)
                <p><strong>Lampiran:</strong> <a href="{{ $surat->lampiran_url }}" target="_blank">Lihat Lampiran</a> (Jika dicetak, lampiran tidak ikut tercetak di sini)</p>
            @endif
        </div>

        <div class="footer clearfix">
            <div class="signature-block" style="float: left; width: 40%; text-align:center;">
                <p>Pegawai yang Mengajukan,</p>
                <br><br><br>
                <p>( {{ $surat->pegawai->name }} )</p>
            </div>
            @if($surat->status === 'disetujui' && $surat->pimpinan)
            <div class="signature-block" style="float: right; width: 40%; text-align:center;">
                <p>Mengetahui/Menyetujui,</p>
                <p>Pimpinan,</p>
                <br><br><br>
                <p>( {{ $surat->pimpinan->name }} )</p>
            </div>
            @endif
        </div>
    </div>
</body>
</html>