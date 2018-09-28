<div class="display-block future-start-date">
  {ts}Start Date{/ts}: {$receive_date|truncate:10:''|crmDate}
</div>
{literal}
<script type="text/javascript">
  CRM.$(function($) {
    $('.future-start-date').appendTo('.amount_display-group');
  });
</script>
{/literal}


