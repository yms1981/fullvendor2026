    </tbody>
</table>

<table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 16px;">
    <tr>
        <td width="55%"></td>
        <td width="45%" valign="top">
            <table width="100%" cellpadding="0" cellspacing="0" style="border: 1px solid #e2e8f0;">
                @if($subtotal > 0)
                <tr>
                    <td style="padding: 8px 12px; background: #f8fafc; color: #64748b; font-size: 8.5pt; font-weight: 700; text-transform: uppercase;">{{ $subtotalLabel }}</td>
                    <td align="right" style="padding: 8px 12px; background: #f8fafc; font-weight: 600;">{{ $moneda }} {{ $fmtMoney($subtotal) }}</td>
                </tr>
                @endif
                @if($discount > 0)
                <tr>
                    <td style="padding: 8px 12px; border-top: 1px solid #e2e8f0; color: #64748b; font-size: 8.5pt; font-weight: 700; text-transform: uppercase;">{{ $discountLabel }}</td>
                    <td align="right" style="padding: 8px 12px; border-top: 1px solid #e2e8f0; color: #dc2626; font-weight: 600;">- {{ $moneda }} {{ $fmtMoney($discount) }}</td>
                </tr>
                @endif
                <tr>
                    <td style="padding: 11px 12px; background: #0f172a; color: #ffffff; font-size: 9pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">{{ $totalLabel }}</td>
                    <td align="right" style="padding: 11px 12px; background: #0f172a; color: #ffffff; font-size: 12pt; font-weight: 700;">{{ $moneda }} {{ $fmtMoney($grandTotal) }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

@if(!empty($notesText))
<div class="notes-box">
    <p class="notes-title">{{ $notesLabel }}</p>
    <p class="notes-content">{{ $notesText }}</p>
</div>
@endif

<table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 22px;">
    <tr>
        <td style="border-top: 1px solid #e2e8f0; padding-top: 10px;" class="small muted" align="center">
            FullVendor · {{ $companyName !== '' ? $companyName : 'Order document' }}
        </td>
    </tr>
</table>

</body>
</html>
