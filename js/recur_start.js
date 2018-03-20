/* custom js for public selection of future recurring start dates */
/* only show option when recurring is selected */
/*jslint indent: 2 */
/*global CRM, ts */

function paperlessRecurStartRefresh() {
  cj(function ($) {
    'use strict';
     $('.is_recur-section').after($('#paperless-recurring-start-date'));
     cj('input[id="is_recur"]').on('change', function() {
       toggleRecur();
     });
     toggleRecur();

     function toggleRecur( ) {
       var isRecur = cj('input[id="is_recur"]:checked');
       if (isRecur.val() > 0) {
         cj('#paperless-recurring-start-date').show().val('');
       }
       else {
         cj('#paperless-recurring-start-date').hide();
         $("#paperless-recurring-start-date option:selected").prop("selected", false);
         $("#paperless-recurring-start-date option:first").prop("selected", "selected");
       }
     }
  });
}
