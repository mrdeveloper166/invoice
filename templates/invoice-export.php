<?php
// Expected variables:
// - $company (array)
// - $data (invoice + client fields)
// - $items_arr (array of invoice items)

$isPaid = isset($data['status']) && strtolower($data['status']) === 'paid';
$statusLabel = $isPaid ? 'PAID' : 'UNPAID';
$statusClass = $isPaid ? 'paid' : 'unpaid';
$exportCurrency = $data['currency'] ?? 'USD';
$curSym = getCurrencySymbol($exportCurrency);
?>
<style>
  /* Print page setup */
  @page{
    size: A4 portrait;
    margin: 10mm 10mm 16mm 10mm;
  }

  .invoice-shell{
    max-width: 960px;
    margin: 24px auto;
    padding: 24px;
  }
  .invoice-wrap{
    position: relative;
    overflow: hidden;
    background:#ffffff;
    border-radius:10px;
    box-shadow:0 8px 25px rgba(15,23,42,0.08);
    padding:28px 32px 24px;
    font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
    color:#0f172a;
    font-size:13px;
  }
  .invoice-body{
    position: relative;
    z-index: 1;
    display:flex;
    flex-direction:column;
    min-height:100%;
  }
  .invoice-watermark{
    position:absolute;
    inset:0;
    display:flex;
    align-items:center;
    justify-content:center;
    z-index:0;
    pointer-events:none;
  }
  .invoice-watermark img{
    max-width:55%;
    max-height:70%;
    width:auto;
    height:auto;
    object-fit:contain;
    opacity:0.1;
  }
  .inv-header{
    display:flex;
    justify-content:space-between;
    gap:24px;
    align-items:flex-start;
  }
  .brand-left{
    display:flex;
    gap:16px;
    align-items:flex-start;
  }
  .brand-left img{
    display:block;
    max-height:56px;
    width:auto;
  }
  .brand-text h3{
    margin:0;
    font-size:20px;
    font-weight:800;
    letter-spacing:0.4px;
  }
  .brand-text p{
    margin:6px 0 0;
    font-size:12px;
    font-weight:600;
    line-height:1.5;
    color:#64748b;
  }
  .meta-right{
    text-align:right;
    font-size:12px;
  }
  .meta-right .doc-title{
    font-size:24px;
    font-weight:800;
    letter-spacing:1px;
    text-transform:uppercase;
    margin:0 0 4px;
  }
  .status-pill{
    display:inline-flex;
    align-items:center;
    padding:2px 10px;
    border-radius:999px;
    font-size:11px;
    font-weight:700;
    margin-top:4px;
  }
  .status-pill.paid{
    background:#dcfce7;
    color:#166534;
    border:1px solid #22c55e;
  }
  .status-pill.unpaid{
    background:#fee2e2;
    color:#b91c1c;
    border:1px solid #ef4444;
  }
  .meta-grid{ margin-top:8px; }
  .meta-grid div{ line-height:1.8; }
  .divider{
    margin:18px 0;
    border-bottom:1px solid #e2e8f0;
  }
  .party-row{
    display:flex;
    gap:24px;
    margin-bottom:8px;
  }
  .party-col{
    flex:1;
    font-size:14px;
    line-height:1.5;
  }
  .label-heading{
    font-size:14px;
    font-weight:700;
    letter-spacing:0.08em;
    text-transform:uppercase;
    color:#6b7280;
    margin-bottom:4px;
  }
  .party-name{
    font-weight:700;
    margin-bottom:2px;
  }
  .muted{ color:#64748b; }
  .note-box{
    margin-top:8px;
    padding:10px 12px;
    background:#f1f7ff;
    border:1px solid #dbe9ff;
    font-size:12px;
    color:#1e3a8a;
  }
  table.items{
    width:100%;
    border-collapse:collapse;
    font-size:14px;
    margin-top:12px;
  }
  table.items thead th{
    background:#f9fafb;
    color:#111827;
    font-weight:700;
    text-transform:uppercase;
    font-size:14px;
    letter-spacing:0.04em;
    padding:8px 10px;
    border-bottom:1px solid #e2e8f0;
  }
  table.items td{
    padding:9px 10px;
    border-bottom:1px solid #e5e7eb;
    vertical-align:top;
  }
  .text-right{ text-align:right; white-space:nowrap; }
  .text-center{ text-align:center; }
  .totals-wrap{
    display:flex;
    justify-content:flex-end;
    margin-top:14px;
  }
  table.summary{
    width:320px;
    border-collapse:collapse;
    font-size:14px;
  }
  table.summary th,
  table.summary td{
    padding:7px 10px;
  }
  table.summary tr:not(.grand) th{
    color:#4b5563;
    font-weight:600;
  }
  table.summary tr:not(.grand) td{
    text-align:right;
    color:#111827;
  }
  table.summary tr:not(.grand){
    border-bottom:1px dashed #e5e7eb;
  }
  .grand{
    background:#0f172a;
    color:#f9fafb;
  }
  .grand th{
    font-weight:700;
  }
  .grand td{
    font-weight:800;
    font-size:13px;
    text-align:right;
  }
  .footer-grid{
    display:flex;
    gap:20px;
    margin-top:18px;
    align-items:flex-start;
  }
  .bank{
    flex:1.2;
    font-size:14px;
    line-height:1.6;
    color:#4b5563;
  }
  .notes{
    flex:1;
    font-size:11px;
    color:#6b7280;
  }
  .sign{
    width:260px;
    text-align:right;
    font-size:14px;
  }
  .sign img{
    margin-top:8px;
    max-width:170px;
    height:auto;
  }
  .note-label{
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:0.08em;
    font-size:14px;
    margin-bottom:4px;
  }
  .print-footnote{
    margin-top: auto;
    padding-top: 10px;
    text-align:center;
    font-size:12px;
    color:#6b7280;
    border-top:1px solid #e5e7eb;
  }
  @media print{
    .invoice-shell{
      margin:0;
      padding:0;
      background:#fff;
    }
    .invoice-wrap{
      border-radius:0;
      box-shadow:none;
      padding:0;
    }
    .invoice-body{
      padding-bottom:12mm;
    }
    .footer-grid{
      margin-top:10px;
    }
    body{ -webkit-print-color-adjust: exact; print-color-adjust: exact; }

    .invoice-watermark img{
      opacity:0.1 !important;
    }

    .print-footnote{
      position: fixed;
      left: 10mm;
      right: 10mm;
      bottom: 5mm;
      margin: 0;
      padding: 4px 0 0;
      border-top: 1px solid #e5e7eb;
      background: #fff;
    }
  }
</style>

<div class="invoice-shell">
  <div class="invoice-wrap">
    <?php if(!empty($company['logo'])){ ?>
      <div class="invoice-watermark" aria-hidden="true">
        <img src="../../uploads/logo/<?php echo htmlspecialchars($company['logo'], ENT_QUOTES); ?>" alt="">
      </div>
    <?php } ?>
    <div class="invoice-body">
    <div class="inv-header">
      <div class="brand-left">
        <?php if(!empty($company['logo'])){ ?>
          <img src="../../uploads/logo/<?php echo $company['logo']; ?>" alt="Logo">
        <?php } ?>
        <div class="brand-text">
          <h3><?php echo $company['company_name']; ?></h3>
          <p>
            <?php echo nl2br($company['address']); ?><br>
            GSTIN: <?php echo $company['gstin']; ?> &nbsp; | &nbsp;
            PAN: <?php echo $company['pan']; ?><br>
            Phone: <?php echo $company['phone']; ?> &nbsp; | &nbsp;
            Email: <?php echo $company['email']; ?><br>
            Website: <?php echo $company['website']; ?>
          </p>
        </div>
      </div>
      <div class="meta-right">
        <p class="doc-title">EXPORT INVOICE</p>
        <div class="meta-grid">
          <div><b>Invoice No:</b> <?php echo $data['invoice_number']; ?></div>
          <div><b>Invoice Date:</b> <?php echo $data['invoice_date']; ?></div>
          <div><b>Financial Year:</b> <?php echo $data['financial_year']; ?></div>
        </div>
        <div class="status-pill <?php echo $statusClass; ?>">
          <?php echo $statusLabel; ?>
        </div>
      </div>
    </div>

    <div class="divider"></div>

    <div class="party-row">
      <div class="party-col">
        <div class="label-heading">Invoiced To</div>
        <div class="party-name"><?php echo $data['client_name']; ?></div>
        <div class="muted"><b>Address:</b> <?php echo nl2br($data['address']); ?></div>
        <?php if(!empty($data['email'])){ ?>
          <div class="muted"><b>Email:</b> <?php echo $data['email']; ?></div>
        <?php } ?>
        <?php if(!empty($data['phone'])){ ?>
          <div class="muted"><b>Phone:</b> <?php echo $data['phone']; ?></div>
        <?php } ?>
      </div>
      <div class="party-col">
        <div class="label-heading">Export Details</div>
        <div class="muted">
          <b>Currency:</b> <?php echo $data['currency']; ?><br>
        </div>
      </div>
    </div>

    <table class="items">
      <thead>
        <tr>
          <th>Description</th>
          <th class="text-center" style="width:70px">Qty</th>
          <th class="text-right" style="width:120px">Rate </th>
          <th class="text-right" style="width:130px">Amount</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($items_arr as $item){ ?>
          <tr>
            <td><?php echo $item['description']; ?></td>
            <td class="text-center"><?php echo $item['qty']; ?></td>
            <td class="text-right"><?php echo formatMoney($item['unit_price'], $exportCurrency); ?></td>
            <td class="text-right"><?php echo formatMoney($item['amount'], $exportCurrency); ?></td>
          </tr>
        <?php } ?>
      </tbody>
    </table>

    <div class="totals-wrap">
      <table class="summary">
        <tr>
          <th>Subtotal</th>
          <td><?php echo formatMoney($data['subtotal'], $exportCurrency); ?></td>
        </tr>
        <tr class="grand">
          <th>Grand Total</th>
          <td><?php echo formatMoney($data['grand_total'], $exportCurrency); ?></td>
        </tr>

      </table>
    </div>
    <div class="footer-grid">
     <div class="bank">
  <div class="note-label">Bank Details</div>

  <table style="width: 70%; border-collapse: collapse;">
    <tr>
      <td style="padding: 4px 0;"><strong>Company Name:</strong></td>
      <td style="padding: 4px 0;"><?php echo $company['company_name']; ?></td>
    </tr>
    <tr>
      <td style="padding: 4px 0;"><strong>Bank:</strong></td>
      <td style="padding: 4px 0;"><?php echo $company['bank_name']; ?></td>
    </tr>
    <tr>
      <td style="padding: 4px 0;"><strong>Account:</strong></td>
      <td style="padding: 4px 0;"><?php echo $company['account_number']; ?></td>
    </tr>
    <tr>
      <td style="padding: 4px 0;"><strong>IFSC:</strong></td>
      <td style="padding: 4px 0;"><?php echo $company['ifsc']; ?></td>
    </tr>
    <tr>
      <td style="padding: 4px 0;"><strong>SWIFT Code:</strong></td>
      <td style="padding: 4px 0;"><?php echo $company['swift_code']; ?></td>
    </tr>
  </table>
</div>
      <div class="sign">
        <div class="note-label">Authorized Signature</div>
        <?php if(!empty($company['signature'])){ ?>
          <img src="../../uploads/signature/<?php echo $company['signature']; ?>" alt="Signature">
        <?php } ?>
        
      </div>
    </div>
    <div class="note-box">
        Supply meant for export under LUT without payment of IGST.
       </div>
    <div class="print-footnote">
    This is a computer generated invoice and does not require a physical signature.
    </div>
    </div>
  </div>
</div>
