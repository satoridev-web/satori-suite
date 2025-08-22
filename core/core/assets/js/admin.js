(function($){
  $(function(){

      /* -------------------------------------------------
       * Copy diagnostics button
       * -------------------------------------------------*/
      $('#satori-copy-diagnostics').on('click', function(e){
          e.preventDefault();
          const btn = $(this);
          const status = $('#satori-copy-diagnostics-status');
          status.text('');

          $.post(SatoriCoreAdmin.ajaxurl, {
              action: 'satori_core_export_settings',
              nonce: SatoriCoreAdmin.nonce
          }).done(function(resp){
              if (resp.success) {
                  const text = JSON.stringify(resp.data.settings, null, 2);
                  navigator.clipboard.writeText(text).then(() => {
                      status.text(SatoriCoreAdmin.i18nCopied || 'Diagnostics copied.');
                  });
              } else {
                  status.text(resp.data?.message || 'Error.');
              }
          });
      });

      /* -------------------------------------------------
       * Test log button
       * -------------------------------------------------*/
      $('#satori-write-test-log').on('click', function(e){
          e.preventDefault();
          const status = $('#satori-test-log-status');
          status.text('');

          $.post(SatoriCoreAdmin.ajaxurl, {
              action: 'satori_core_write_test_log',
              nonce: SatoriCoreAdmin.nonce
          }).done(function(resp){
              status.text(resp.success ? (resp.data.message || 'OK') : 'Error.');
          });
      });

      /* -------------------------------------------------
       * Advanced tab buttons
       * -------------------------------------------------*/
      $('#satori-recheck-updates').on('click', function(){
          ajaxAction('satori_core_recheck_updates', '#satori-advanced-status');
      });
      $('#satori-clear-caches').on('click', function(){
          ajaxAction('satori_core_clear_caches', '#satori-advanced-status');
      });
      $('#satori-export-settings').on('click', function(){
          ajaxAction('satori_core_export_settings', '#satori-advanced-status', true);
      });
      $('#satori-view-log').on('click', function(){
          window.open(SatoriCoreAdmin.streamLogUrl, '_blank');
      });

      function ajaxAction(action, statusSelector, dumpJson){
          const status = $(statusSelector);
          status.text('');
          $.post(SatoriCoreAdmin.ajaxurl, {
              action: action,
              nonce: SatoriCoreAdmin.nonce
          }).done(function(resp){
              if (!resp.success) {
                  status.text(resp.data?.message || 'Error.');
              } else if (dumpJson) {
                  const blob = new Blob(
                      [JSON.stringify(resp.data.settings || {}, null, 2)],
                      {type: 'application/json'}
                  );
                  const url = URL.createObjectURL(blob);
                  const a = document.createElement('a');
                  a.href = url;
                  a.download = 'satori-settings.json';
                  a.click();
                  URL.revokeObjectURL(url);
                  status.text('Exported.');
              } else {
                  status.text(resp.data.message || 'OK');
              }
          });
      }

      /* -------------------------------------------------
       * Accessible tooltips for .satori-tooltip
       * -------------------------------------------------*/
      $('.satori-tooltip').each(function(){
          const $icon = $(this);
          const title = $icon.attr('title');
          if (!title) return;

          // Create tooltip element
          const $tip = $('<span class="satori-tooltip-popup" role="tooltip"></span>')
              .text(title)
              .hide();
          $icon.removeAttr('title')
               .attr('tabindex','0')
               .attr('aria-describedby','satori-tip-' + Math.random().toString(36).substr(2,6));
          $icon.after($tip);

          function showTip(){
              $tip.fadeIn(150);
          }
          function hideTip(){
              $tip.fadeOut(150);
          }

          $icon.on('mouseenter focus', showTip)
               .on('mouseleave blur', hideTip);
      });

  });
})(jQuery);
