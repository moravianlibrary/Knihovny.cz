/* exported loadHolds */
/* global loadCovers, VuFind, hideLibraryCardContent, showLibraryCardContent */
function loadHolds(element) {
  loadCovers();

  // Checkbox select all
  $(element).find('.checkbox-select-all').on('change', function selectAllCheckboxes() {
    var form = this.form ? $(this.form) : $(this).closest('form');
    if (this.checked) {
      form.find('.checkbox-select-item:not(:checked)').trigger('click');
    } else {
      form.find('.checkbox-select-item:checked').trigger('click');
    }
    $('[form="' + form.attr('id') + '"]').prop('checked', this.checked);
    form.find('.checkbox-select-all').prop('checked', this.checked);
    form.find('.checkbox-select-all[form="' + form.attr('id') + '"]').prop('checked', this.checked);
  });

  var confirmCancelRequest = function confirmCancelRequest(link, action) {
    $(element).find('#cancelConfirm').val(1);
    $(element).find('#submitType').attr('name', action);
    $(link).parents('form').submit();
  };

  $(element).find('#confirm_cancel_selected_yes').click(function cancelSelectedRequests(e) {
    e.preventDefault();
    confirmCancelRequest(this, 'cancelSelected');
  });
  $(element).find('#confirm_cancel_all_yes').click(function cancelAllRequests(e) {
    e.preventDefault();
    confirmCancelRequest(this, 'cancelAll');
  });
  $(element).find('.confirm_cancel_no').click(function doNotCancelRequest(e) {
    e.preventDefault();
  });
  $(element).find('#update_selected').click(function updateSelected() {
    // Change submitType to indicate that this is not a cancel request:
    $(element).find('#submitType').attr('name', 'updateSelected');
  });

  var checkCheckboxes = function CheckCheckboxes() {
    var checked = $(element).find('form[name="updateForm"] .result input[type=checkbox]:checked');
    if (checked.length > 0) {
      $(element).find('#update_selected').removeAttr('disabled');
      $(element).find('#cancelSelected').removeAttr('disabled');
    } else {
      $(element).find('#update_selected').attr('disabled', 'disabled');
      $(element).find('#cancelSelected').attr('disabled', 'disabled');
    }
  };
  $(element).find('form[name="updateForm"] .result input[type=checkbox]').on('change', checkCheckboxes);
  $(element).find('#update_selected').removeClass('hidden');
  checkCheckboxes();

  $(element).find("form").submit(function onSubmit(event) {
    event.preventDefault();
    hideLibraryCardContent(element);
    var button = $(document.activeElement);
    var form = $(this);
    var url = $(element).data('action');
    var data = form.serialize() + '&' + button.attr('id') + '=' + button.val()
      + '&cardId=' + $(element).data('card-id');
    var cache = $(this).attr("data-clear-account-cache");
    $.ajax({
      type: "POST",
      url: url,
      data: data,
      success: function onSuccess(content) {
        showLibraryCardContent(element, content, function onComplete(){
          loadHolds(element);
          VuFind.account.notify(cache, 'undefined');
        });
      }
    });
  });
}