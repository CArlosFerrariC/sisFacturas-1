<table>
    @foreach($imagenes as $img)
        @if ($img->pedido_id == $pedido->id)
            <tr class="adjuntos" data-adjunto="{{ $img->adjunto }}">
                <td>
                   {{--<a href="{{ route('pedidos.descargaradjunto', $img->adjunto) }}">{{ $img->adjunto }}</a>--}}
                    <a target="_blank" download href="{{ \Storage::disk('pstorage')->url('adjuntos/'. $img->adjunto) }}">
                        <span class="text-primary">{{ $img->adjunto }}<span>
                    </a>
                </td>
                <td>
                    
                    <a href="#" style="margin-left: 12px;" class="d-none" data-imgid="{{ $img->pedido_id }}" data-imgadjunto="{{ $img->adjunto }}">dsds</a>

                <a href="" data-target="#modal-delete-adjunto" data-imgid="{{ $img->pedido_id }}" data-imgadjunto="{{ $img->id }}" data-imgadjuntoconfirm="{{ $img->confirm }}" data-toggle="modal">
                    <button class="btn-delete-adjunto btn btn-danger btn-sm" ><i class="fas fa-trash-alt"></i></button>
                </a>

                  
                </td>
            </tr>
        @endif

    @endforeach
</table>

