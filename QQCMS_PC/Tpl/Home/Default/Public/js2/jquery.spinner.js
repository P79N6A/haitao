(function ($) {
  $.fn.spinner = function (opts) {
    return this.each(function () {
      var totalstock = $(".sys_item_mktprice").html()
      var totalprice = $(".sys_item_price").html()
      var defaults = {value:1, min:1}
      var options = $.extend(defaults, opts)
      var keyCodes = {up:38, down:40,back:8}
      var container = $('<div></div>')
      container.addClass('spinner')
      var textField = $(this).addClass('value').attr('maxlength', '2').val(options.value)
        .bind('keyup paste change', function (e) {
          var field = $(this)
          if (e.keyCode >47 && e.keyCode < 57 || e.keyCode==keyCodes.back || e.keyCode==229)
          {
            changeValue(0)
          }
          if (e.keyCode == keyCodes.up) changeValue(1)
          else if (e.keyCode == keyCodes.down) changeValue(-1)
          else if (getValue(field) != container.data('lastValidValue')) validateAndTrigger(field)
        })
      textField.wrap(container)

      var increaseButton = $('<button class="increase">+</button>').click(function () { changeValue(1) })
      var decreaseButton = $('<button class="decrease">-</button>').click(function () { changeValue(-1) })

      validate(textField)
      container.data('lastValidValue', options.value)
      textField.before(decreaseButton)
      textField.after(increaseButton)

      function changeValue(delta) {
        var num_delta = getValue() + delta
        var num_price = totalprice * num_delta

        textField.val(getValue() + delta)
        if (num_price >= 1000)
        {
          var isnum = parseInt(totalprice/1000)
          alert('抱歉，您已超过海关限额¥1000，请分次购买！')
          if (isnum>0)
          textField.val(getValue() - delta)
          else
          textField.val(1)
        }

        if (num_delta > _stock)
        {
          textField.val(_stock)
        } 
        validateAndTrigger(textField)
      }

      function validateAndTrigger(field) {
        clearTimeout(container.data('timeout'))
        var value = validate(field)
        if (!isInvalid(value)) {
          textField.trigger('update', [field, value])
        }
      }

      function validate(field) {

        var value = getValue()
        if (value >= totalstock) increaseButton.attr('disabled', 'disabled')
        else increaseButton.removeAttr('disabled')
        field.toggleClass('invalid', isInvalid(value)).toggleClass('passive', value === 0)

        if (value <= options.min) decreaseButton.attr('disabled', 'disabled')
        else decreaseButton.removeAttr('disabled')
        field.toggleClass('invalid', isInvalid(value)).toggleClass('passive', value === 0)

        if (isInvalid(value)) {
          var timeout = setTimeout(function () {
            textField.val(container.data('lastValidValue'))
            validate(field)
          }, 500)
          container.data('timeout', timeout)
        } else {
          container.data('lastValidValue', value)
        }
        return value
      }

      function isInvalid(value) { return isNaN(+value) || value < options.min; }

      function getValue(field) {
        field = field || textField;
        return parseInt(field.val() || 0, 10)
      }
    })
  }
})(jQuery)