<?php include __DIR__."/../config/config.php"; ?>
<div class="app-sidebar" id="appSidebar" aria-label="Sidebar navigation">

<div class="sidebar-brand px-3 pt-3 pb-2">
  <div class="d-flex align-items-center gap-2">
    <?php if(!empty($company['logo']) && file_exists(__DIR__.'/../uploads/logo/'.basename($company['logo']))){ ?>
    <img src="<?php echo $base_url; ?>uploads/logo/<?php echo rawurlencode(basename($company['logo'])); ?>"
         alt="Company Logo"
         class="sidebar-logo">
    <?php }else{ ?>
    <div class="sidebar-logo-placeholder">
      <i class="fa fa-building"></i>
    </div>
    <?php } ?>
    <h5 class="m-0 sidebar-company-name"><?php echo htmlspecialchars($company['company_name']); ?></h5>
  </div>
  <button type="button" class="btn btn-sm btn-light d-lg-none sidebar-close-btn" id="sidebarClose" aria-label="Close menu">
    <i class="fa fa-times"></i>
  </button>
</div>

<hr class="sidebar-divider mx-3 my-2">

<a href="<?php echo $base_url; ?>index.php">
<i class="fa fa-home"></i> Dashboard
</a>

<a href="<?php echo $base_url; ?>modules/clients/add-client.php">
<i class="fa fa-user-plus"></i> Add Client
</a>

<a href="<?php echo $base_url; ?>modules/clients/client-list.php">
<i class="fa fa-users"></i> Manage Clients
</a>

<a href="<?php echo $base_url; ?>modules/invoices/create-invoice.php">
<i class="fa fa-file-invoice"></i> Create Invoice
</a>

<a href="<?php echo $base_url; ?>modules/invoices/invoice-list.php">
<i class="fa fa-list"></i> All Invoices
</a>

<a href="<?php echo $base_url; ?>modules/invoices/recycle-bin.php">
<i class="fa fa-trash-restore"></i> Recycle Bin
</a>

<a href="<?php echo $base_url; ?>modules/reports/reports.php">
<i class="fa fa-chart-line"></i> Reports
</a>

<a href="<?php echo $base_url; ?>modules/settings/company-profile.php">
<i class="fa fa-building"></i> Company Profile
</a>

<a href="<?php echo $base_url; ?>logout.php">
<i class="fa fa-sign-out-alt"></i> Logout
</a>

</div>

<style>
.sidebar-brand{
  position:relative;
}
.sidebar-brand .sidebar-close-btn{
  position:absolute;
  top:12px;
  right:12px;
}
.sidebar-logo{
  width:42px;
  height:42px;
  object-fit:contain;
  border-radius:8px;
  background:#fff;
  padding:3px;
  flex-shrink:0;
}
.sidebar-logo-placeholder{
  width:42px;
  height:42px;
  border-radius:8px;
  background:rgba(255,255,255,0.15);
  display:flex;
  align-items:center;
  justify-content:center;
  flex-shrink:0;
  font-size:18px;
}
.sidebar-company-name{
  font-size:15px;
  line-height:1.3;
  word-break:break-word;
}
.sidebar-divider{
  border-color:rgba(255,255,255,0.25);
  opacity:1;
}
</style>
