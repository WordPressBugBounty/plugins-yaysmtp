(function($) {
  $(document).ready(function() {

     /*********************** -------------------- Active menu item classes - start -------------------- ***********************/
     const parentClass = '.toplevel_page_yaycommerce';
     const logsHashNeedle = '#/email-logs';
     const logsHrefNeedles = [
       'yaysmtp#/email-logs',
       'yaysmtp%23%2Femail-logs',
       'yaysmtp%23/email-logs',
     ];

     function isYaysmtpAdminPage() {
       return /[?&]page=yaysmtp(&|$)/.test(window.location.search);
     }

     function isEmailLogsUrl() {
       const href = window.location.href || '';
       const hash = window.location.hash || '';
       if (hash.indexOf('/email-logs') !== -1) return true;
       for (let i = 0; i < logsHrefNeedles.length; i++) {
         if (href.indexOf(logsHrefNeedles[i]) !== -1) return true;
       }
       return false;
     }

     function isEmailLogsHref(href) {
       if (!href) return false;
       if (href.indexOf(logsHashNeedle) !== -1) return true;
       for (let i = 0; i < logsHrefNeedles.length; i++) {
         if (href.indexOf(logsHrefNeedles[i]) !== -1) return true;
       }
       return false;
     }
 
     function clearSubmenuClasses() {
       const lis = document.querySelectorAll(parentClass + ' .wp-submenu li');
       lis.forEach(function(li) {
         li.classList.remove('wp-first-item');
         li.classList.remove('current');
 
         const a = li.querySelector('a');
         if (a) {
           a.removeAttribute('aria-current');
         }
       });
     }
 
     function setActiveEmailLogs() {
       clearSubmenuClasses();
 
       const links = document.querySelectorAll(parentClass + ' .wp-submenu li a');
       let logsLink = null;
 
       links.forEach(function(a) {
         if (logsLink) return;
         const href = a.getAttribute('href') || '';
         if (isEmailLogsHref(href)) {
           logsLink = a;
         }
       });
 
       if (!logsLink) return;
 
       const targetLi = logsLink.closest('li');
       if (!targetLi) return;
 
       targetLi.classList.add('wp-first-item', 'current');
       logsLink.setAttribute('aria-current', 'page');
     }
 
     function setActiveMain() {
       clearSubmenuClasses();
 
       const links = document.querySelectorAll(parentClass + ' .wp-submenu li a');
       let mainLink = null;
 
       links.forEach(function(a) {
         if (mainLink) return;
         const href = a.getAttribute('href') || '';
         const isMain = href.indexOf('page=yaysmtp') !== -1 && !isEmailLogsHref(href);
         if (isMain) {
           mainLink = a;
         }
       });
 
       if (!mainLink) return;
 
       const targetLi = mainLink.closest('li');
       if (!targetLi) return;
 
       targetLi.classList.add('wp-first-item', 'current');
       mainLink.setAttribute('aria-current', 'page');
     }
 
     function syncFromHash() {
       if (!isYaysmtpAdminPage()) return;

       if (isEmailLogsUrl()) {
         setActiveEmailLogs();
       } else {
         setActiveMain();
       }
     }
 
     // Click header navigation menu list
     $('body').on('click', '.yaysmtp-ui ul[data-slot="header-navigation-menu-list"] a', function() {
       const href = $(this).attr('href') || '';
       if (href === '#/email-logs') {
         setActiveEmailLogs();
       } else if (href.includes('#/dashboard') || href.includes('#/settings') || href.includes('#/tools') || href.includes('#/email-reports')) {
         setActiveMain();
       }
     });
 
     // Click submenu: ensure "wp-first-item current" is on email logs.
     // (WP sets only `current` on inital load; for hash routes, this helps keep the UI consistent.)
     $('body').on('click', parentClass + ' ul.wp-submenu a', function() {
       const href = $(this).attr('href') || '';
       if (isEmailLogsHref(href)) {
         setActiveEmailLogs();
       } else if (href.indexOf('page=yaysmtp') !== -1) {
         setActiveMain();
       }
     });

     // Initial load: e.g. admin.php?page=yaysmtp#/email-logs (no click needed).
     syncFromHash();
     window.addEventListener('hashchange', syncFromHash);

     /*********************** -------------------- Active menu item classes - end -------------------- ***********************/

    $("body").on(
      "click",
      ".yaysmtp-import-settings-notice .close-btn",
      function() {
        $(".yaysmtp-import-settings-notice").remove();
        $.ajax({
          url: yaySmtpWpGlobalData.YAY_ADMIN_AJAX,
          type: "POST",
          data: {
            action: "yaysmtp_close_popup_import_smtp_settings",
            nonce: yaySmtpWpGlobalData.ajaxNonce
          },
          success: function(result) {}
        });
      }
    );
  });
})(window.jQuery);

