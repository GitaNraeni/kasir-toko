<div class="card card-orange card-outline">
    <div class="card-body">
        <h3 class="m-0 text-right">
            Rp <span id="totalJumlah">0</span>,-
        </h3>
    </div>
</div>

<form action="{{ route('transaksi.store') }}" method="POST" class="card card-orange card-outline">
    @csrf
    <div class="card-body">
        <p class="text-right">
            Tanggal: {{ $tanggal }}
        </p>
        <div class="row">
    <div class="col">
        <div class="form-group">
            <label for="namaPelanggan">Nama Pelanggan</label>
            <input type="text" id="namaPelanggan" 
                class="form-control @error('pelanggan_id') is-invalid @enderror" 
                disabled>
            @error('pelanggan_id')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror
            <input type="hidden" name="pelanggan_id" id="pelangganId">
        </div>
    </div>

    <div class="col">
            <label>Nama Kasir</label>
            <input type="text" class="form-control" value="{{ $nama_kasir }}" disabled>
        </div>
    </div>
</div>

<table class="table table-striped table-hover table-bordered mt-3">
    <thead>
        <tr>
            <th>Nama Produk</th>
            <th>Qty</th>
            <th>Harga</th>
            <th>Sub Total</th>
            <th></th>
        </tr>
    </thead>
    <tbody id="resultCart">
        <tr>
            <td colspan="5" class="text-center">Tidak ada data.</td>
        </tr>
    </tbody>
</table>
<div class="row mt-3">
    <div class="col-2 offset-6">
        <p>Total</p>
        <p>Pajak 10%</p>
        <p>Total Bayar</p>
    </div>

    <div class="col-4 text-right">
        <p id="subtotal">0</p>
        <p id="taxAmount">0</p>
        <p id="total">0</p>
    </div>
</div>

<div class="row mt-2">
    <div class="col-6 offset-6">
        <hr class="mt-0">
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text">Cash</span>
            </div>
            <input type="text" name="cash" 
                class="form-control @error('cash') is-invalid @enderror"
                placeholder="Jumlah Cash" value="{{ old('cash') }}">
        </div>

        <input type="hidden" name="total_bayar" id="totalBayar" />

        @error('cash')
            <div class="invalid-feedback d-block">
                {{ $message }}
            </div>
        @enderror
    </div>
</div>

<div class="row mt-3">
    <div class="col-12 form-inline mt-3">
        <a href="{{ route('transaksi.index') }}" class="btn btn-secondary mr-2">Ke Transaksi</a>
        <a href="{{ route('cart.clear') }}" class="btn btn-danger">Kosongkan</a>
    <button type="submit" class="btn btn-success ml-auto">
    <i class="fas fa-money-bill-wave mr-2"></i> Bayar Transaksi
</button>
</div>
</div>
</form> 

@push('scripts')
<script>
    $(function() {
        fetchCart();
    });

    function fetchCart() {
        $.getJSON("/cart", function(response) {
            $('#resultCart').empty();

            const { items, subtotal, tax_amount, total, extra_info } = response;

            // Tampilkan total
            $('#subtotal').html(rupiah(subtotal));
            $('#taxAmount').html(rupiah(tax_amount));
            $('#total, #totalJumlah').html(rupiah(total));
            $('#totalBayar').val(total);

            // Cek kalau ada item
            if (items && Object.keys(items).length > 0) {
                for (const property in items) {
                    addRow(items[property]);
                }
            } else {
                $('#resultCart').html(`<tr><td colspan="5" class="text-center">Tidak ada data.</td></tr>`);
            }

            // Tampilkan pelanggan jika ada
            if (extra_info && extra_info.pelanggan) {
                const { id, name } = extra_info.pelanggan;
                $('#namaPelanggan').val(name);
                $('#pelangganId').val(id);
            }
        });
    }

    function addRow(item) {
        const { hash, title, quantity, price, total_price, options } = item;

        let btn = `<button type="button" class="btn btn-xs btn-success mr-2" onclick="ePut('${hash}',1)">
                        <i class="fas fa-plus"></i>
                    </button>`;
        btn += `<button type="button" class="btn btn-xs btn-primary mr-2" onclick="ePut('${hash}',-1)">
                        <i class="fas fa-minus"></i>
                    </button>`;
        btn += `<button type="button" class="btn btn-xs btn-danger" onclick="eDel('${hash}')">
                        <i class="fas fa-times"></i>
                    </button>`;

        const { diskon, harga_produk } = options;
        const nilai_diskon = diskon ? `(-${diskon}%)` : "";

        const row = `<tr>
                        <td>${title}</td>
                        <td>
                            <input type="number"
                                value="${quantity}"
                                min="1"
                                data-hash="${hash}"
                                style="width:60px;text-align:center"
                                onchange="updateQty('${hash}', this.value)">
                        </td>
                        <td>${rupiah(harga_produk)}${nilai_diskon}</td>
                        <td>${rupiah(total_price)}</td>
                        <td>${btn}</td>
                    </tr>`;

        $('#resultCart').append(row);
    }

    function rupiah(number) {
        return new Intl.NumberFormat("id-ID").format(number);
    }

    function updateQty(hash, qty) {
        $.ajax({
            type: "PUT",
            url: "/cart/" + hash,
            data: { qty: parseInt(qty) },
            dataType: 'json',
            success: function(response) {
                console.log(response);
                fetchCart();
            }
     });
    }

    function ePut(hash, change) {
        let input = $(`input[data-hash='${hash}']`);
        let currentQty = parseInt(input.val()) || 0;
        let  newQty = currentQty + change;

        if (newQty < 1) {
            newQty = 1;
        }
        $.ajax({
            type: "PUT",
            url: "/cart/" + hash,
            data: { qty: newQty },
            dataType: "json",
            success: function() {
                fetchCart();
            }
        });
    }

    function eDel(hash) {
        $.ajax({
            type: "DELETE",
            url: "/cart/" + hash,
            dataType: "json",
            success: function(response) {
                fetchCart();
            }
        });
    }
</script>
@endpush
