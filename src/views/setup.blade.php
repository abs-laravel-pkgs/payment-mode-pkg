@if(config('PAYMENT_MODE_PKG.DEV'))
    <?php $payment_mode_pkg_prefix = '/packages/abs/payment-mode-pkg/src';?>
@else
    <?php $payment_mode_pkg_prefix = '';?>
@endif

<script type="text/javascript">
    var payment_mode_list_template_url = "{{asset($payment_mode_pkg_prefix.'/public/themes/'.$theme.'/payment-mode-pkg/payment-mode/list.html')}}";
    var payment_mode_form_template_url = "{{asset($payment_mode_pkg_prefix.'/public/themes/'.$theme.'/payment-mode-pkg/payment-mode/form.html')}}";
</script>
<script type="text/javascript" src="{{asset($payment_mode_pkg_prefix.'/public/themes/'.$theme.'/payment-mode-pkg/payment-mode/controller.js')}}"></script>
