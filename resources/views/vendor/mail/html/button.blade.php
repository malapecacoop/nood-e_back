@props([
    'url',
    'color' => 'primary',
    'align' => 'center',
])
<table class="action" align="{{ $align }}" width="100%" cellpadding="0" cellspacing="0" role="presentation">
    <tr>
        <td align="{{ $align }}">
            <table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
                <tr>
                    <td align="{{ $align }}">
                        <table border="0" cellpadding="0" cellspacing="0" role="presentation">
                            <tr>
                                <td>
                                    <a href="{{ $url }}" class="button button-{{ $color }}" target="_blank" rel="noopener"
                                        style="
                                            border-radius: 36px;
                                            border: 1px solid rgba(190, 192, 245);
                                            background: linear-gradient(180deg, #E1F7FB 0%, #898BE8 100%);
                                            padding: 5px 12px;
                                            color: black;
                                        "
                                    >{{ $slot }}</a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
