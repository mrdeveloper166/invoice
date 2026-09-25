<footer class="footer">

<p class="m-0">
© <?php echo date("Y"); ?> <?php echo $company['company_name']; ?>
</p>

</footer>

<?php if(!isset($base_url)){ include __DIR__ . "/../config/config.php"; } ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
(function(){
  const sidebar = document.getElementById('appSidebar');
  const toggleBtn = document.getElementById('sidebarToggle');
  const closeBtn = document.getElementById('sidebarClose');
  const backdrop = document.getElementById('sidebarBackdrop');
  const isMobile = () => window.innerWidth < 992;

  function openSidebar(){
    if(!sidebar) return;
    if(!isMobile()) return;
    sidebar.classList.add('show');
    if(backdrop) backdrop.classList.add('show');
    if(toggleBtn) toggleBtn.classList.add('is-hidden');
  }

  function closeSidebar(){
    if(!sidebar) return;
    sidebar.classList.remove('show');
    if(backdrop) backdrop.classList.remove('show');
    if(toggleBtn) toggleBtn.classList.remove('is-hidden');
  }

  if(toggleBtn){
    toggleBtn.addEventListener('click', function(e){
      e.preventDefault();
      openSidebar();
    });
  }
  if(closeBtn){
    closeBtn.addEventListener('click', function(e){
      e.preventDefault();
      closeSidebar();
    });
  }
  if(backdrop){
    backdrop.addEventListener('click', closeSidebar);
  }

  // Close on ESC
  document.addEventListener('keydown', function(e){
    if(e.key === 'Escape') closeSidebar();
  });

  // When resizing to desktop, ensure sidebar visible and backdrop hidden
  window.addEventListener('resize', function(){
    if(window.innerWidth >= 992){
      if(sidebar) sidebar.classList.remove('show');
      if(backdrop) backdrop.classList.remove('show');
      if(toggleBtn) toggleBtn.classList.remove('is-hidden');
    }
  });
})();

(function(){
  var INACTIVITY_MS = 5 * 60 * 1000;
  var logoutUrl = <?php echo json_encode(rtrim($base_url, '/') . '/logout.php?timeout=1'); ?>;
  var timer = null;
  var lastActivity = Date.now();
  var throttleMs = 1000;
  var lastThrottle = 0;

  function logout(){
    window.location.href = logoutUrl;
  }

  function checkIdle(){
    if(Date.now() - lastActivity >= INACTIVITY_MS){
      logout();
    }
  }

  function resetTimer(){
    lastActivity = Date.now();
    clearTimeout(timer);
    timer = setTimeout(logout, INACTIVITY_MS);
  }

  function onActivity(){
    var now = Date.now();
    if(now - lastThrottle < throttleMs){
      return;
    }
    lastThrottle = now;
    resetTimer();
  }

  ['mousemove', 'mousedown', 'keydown', 'keyup', 'scroll', 'touchstart', 'click', 'wheel'].forEach(function(evt){
    document.addEventListener(evt, onActivity, { passive: true });
  });

  document.addEventListener('visibilitychange', function(){
    if(document.visibilityState === 'visible'){
      checkIdle();
    }
  });

  window.addEventListener('focus', checkIdle);

  resetTimer();
})();
</script>

<style>

.footer{
background:#234999;
color:white;
text-align:center;
padding:12px;
position:relative;
bottom:0;
width:100%;
}

</style>

</body>
</html>