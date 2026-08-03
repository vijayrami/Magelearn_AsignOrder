define([
    'Magento_Ui/js/modal/modal',
    'jquery',
    'mage/translate'
], function ($modal, $, $t) {
    'use strict';

    var assignModal;
    var $emailHref = $('table.order-account-information-table tr a[href^="mailto:"]');

    function showError(message) {
        $('.message-container').html(
            '<div class="message message-error">' +
            '<div>' + message + '</div>' +
            '</div>'
        );
    }

    function showSuccess(message) {
        $('.message-container').html(
            '<div class="message message-success">' +
            '<div>' + message + '</div>' +
            '</div>'
        );
    }

    function clearMessages() {
        $('.message-container').html('');
    }

    function showGridError(message) {
        $('#customer-grid-container').html(
            '<div class="message message-error">' +
            '<div>' + message + '</div>' +
            '</div>'
        );
    }

    var magelearnAssignCustomerPopup = function (config) {
        if (!assignModal) {
            assignModal = $('#magelearn_assign_customer').modal({
                title: $t('Assign Customer'),
                modalClass: 'assign-customer-modal',
                innerScroll: true,
                responsive: true,
                buttons: [{
                    text: $t('Close'),
                    class: 'action-default action-dismiss',
                    click: function () {
                        this.closeModal();
                    }
                }],
                closed: function () {
                    $('#customer-id').val('');
                    $('#customer-grid-container').html(
                        '<div class="admin__data-grid-loading-mask">' +
                        $t('Loading customers...') +
                        '</div>'
                    );
                    clearMessages();
                }
            });
        }

        assignModal.modal('openModal');

        $.ajax({
            url: config.gridUrl,
            type: 'GET',
            showLoader: true,
            success: function (html) {
                $('#customer-grid-container').html(html);
            },
            error: function () {
                showGridError(
                    $t('Unable to load customers.')
                );
            }
        });   
    };

    var assignCustomerFormPost = function (config) {
        var customerId = $('#customer-id').val();
        var $form = $('#magelearn_assign_customer').find('form.assign-order-customer');
        var $assignButton = $('button.assign-customer');
        if (!customerId) {
            showError(
                $t('Please select a customer.')
            );

            return false;
        }

        $assignButton.prop('disabled', true).addClass('disabled');

        var url = $form.attr('action');
        var postData = $form.serialize();

        clearMessages();

        $.ajax({
            url: url,
            dataType: 'json',
            type: 'POST',
            showLoader: true,
            data: postData,
            success: function (response) {
                if (response.error === false) {
                    showSuccess(
                        response.message
                    );
                    setTimeout(function () {
                        assignModal.modal(
                            'closeModal'
                        );
                        if (response.redirectUrl) {
                            window.location.href =
                                response.redirectUrl;
                        }
                    }, 2000);
                } else {
                    showError(
                        response.message
                    );
                }
            },
            error: function () {
                showError(
                    $t(
                        'An error occurred while assigning the order.'
                    )
                );
            },
            complete: function () {
                $assignButton.prop('disabled', false).removeClass('disabled');
            }
        });

        return false;
    };

    return function (config) {
        $(document).on(
            'change',
            'input[name="selected_customer"]',
            function () {
                $('#customer-id').val($(this).val());
                clearMessages();
            }
        );

        if ($emailHref.length > 0) {
            var html = '<button id="magelearnAssignCustomerPopup">' +
                       config.buttonLabel + '</button>';
            if (!$('#magelearnAssignCustomerPopup').length) {
                $emailHref.parent().append(html);
            }

            $(document).on('click', '#magelearnAssignCustomerPopup', function () {
                magelearnAssignCustomerPopup(config);
            });
        }

        $(document).on('click', 'button.assign-customer', function () {
            assignCustomerFormPost(config);
        });

        $(document).on('submit', 'form.assign-order-customer', function (e) {
            e.preventDefault();
        });
    };
});