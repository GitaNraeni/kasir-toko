<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penjualan extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'pelanggan_id',
        'nomor_transaksi',
        'tanggal',
        'total',
        'tunai',
        'kembalian',
        'status',
        'subtotal',
        'pajak'
    ];
    // Relasi ke detail penjualan
    public function detil()
    {
        return $this->hasMany(DetilPenjualan::class, 'penjualan_id');
    }

    // Relasi ke pelanggan
    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class, 'pelanggan_id');
    }

    // Relasi ke user/kasir
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
