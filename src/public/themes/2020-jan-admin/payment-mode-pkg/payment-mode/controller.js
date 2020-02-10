app.config(['$routeProvider', function($routeProvider) {

    $routeProvider.
    when('/payment-mode-pkg/payment-mode/list', {
        template: '<payment-mode-list></payment-mode-list>',
        title: 'Payment Modes',
    }).
    when('/payment-mode-pkg/payment-mode/add', {
        template: '<payment-mode-form></payment-mode-form>',
        title: 'Add Payment Mode',
    }).
    when('/payment-mode-pkg/payment-mode/edit/:id', {
        template: '<payment-mode-form></payment-mode-form>',
        title: 'Edit Payment Mode',
    });
}]);

app.component('paymentModeList', {
    templateUrl: payment_mode_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $location) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        var table_scroll;
        table_scroll = $('.page-main-content').height() - 37;
        var dataTable = $('#payment_modes_list').DataTable({
            "dom": dom_structure,
            "language": {
                "search": "",
                "searchPlaceholder": "Search",
                "lengthMenu": "Rows Per Page _MENU_",
                "paginate": {
                    "next": '<i class="icon ion-ios-arrow-forward"></i>',
                    "previous": '<i class="icon ion-ios-arrow-back"></i>'
                },
            },
            stateSave: true,
            pageLength: 10,
            processing: true,
            serverSide: true,
            paging: true,
            ordering: false,
            ajax: {
                url: laravel_routes['getPaymentModeList'],
                data: function(d) {}
            },
            columns: [
                { data: 'action', searchable: false, class: 'action' },
                { data: 'question', name: 'payment_modes.question', searchable: true },
            ],
            "infoCallback": function(settings, start, end, max, total, pre) {
                $('#table_info').html(total + '/' + max)
            },
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            },
            initComplete: function() {
                $('.search label input').focus();
            },
        });
        $('.dataTables_length select').select2();
        $('.page-header-content .display-inline-block .data-table-title').html('Payment Modes <span class="badge badge-secondary" id="table_info">0</span>');
        $('.page-header-content .search.display-inline-block .add_close_button').html('<button type="button" class="btn btn-img btn-add-close"><img src="' + image_scr2 + '" class="img-responsive"></button>');
        $('.page-header-content .refresh.display-inline-block').html('<button type="button" class="btn btn-refresh"><img src="' + image_scr3 + '" class="img-responsive"></button>');
        $('.add_new_button').html(
            '<a href="#!/payment-mode-pkg/payment_mode/add" type="button" class="btn btn-secondary" dusk="add-btn">' +
            'Add Payment Mode' +
            '</a>'
        );

        $('.btn-add-close').on("click", function() {
            $('#payment_modes_list').DataTable().search('').draw();
        });

        $('.btn-refresh').on("click", function() {
            $('#payment_modes_list').DataTable().ajax.reload();
        });

        $('.dataTables_length select').select2();

        $scope.clear_search = function() {
            $('#search_payment_mode').val('');
            $('#payment_modes_list').DataTable().search('').draw();
        }

        var dataTables = $('#payment_modes_list').dataTable();
        $("#search_payment_mode").keyup(function() {
            dataTables.fnFilter(this.value);
        });

        //DELETE
        $scope.deletePaymentMode = function($id) {
            $('#payment_mode_id').val($id);
        }
        $scope.deleteConfirm = function() {
            $id = $('#payment_mode_id').val();
            $http.get(
                payment_mode_delete_data_url + '/' + $id,
            ).then(function(response) {
                if (response.data.success) {
                    $noty = new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: 'PaymentMode Deleted Successfully',
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 3000);
                    $('#payment_modes_list').DataTable().ajax.reload(function(json) {});
                    $location.path('/payment-mode-pkg/payment_mode/list');
                }
            });
        }

        //FOR FILTER
        $('#payment_mode_code').on('keyup', function() {
            dataTables.fnFilter();
        });
        $('#payment_mode_name').on('keyup', function() {
            dataTables.fnFilter();
        });
        $('#mobile_no').on('keyup', function() {
            dataTables.fnFilter();
        });
        $('#email').on('keyup', function() {
            dataTables.fnFilter();
        });
        $scope.reset_filter = function() {
            $("#payment_mode_name").val('');
            $("#payment_mode_code").val('');
            $("#mobile_no").val('');
            $("#email").val('');
            dataTables.fnFilter();
        }

        $rootScope.loading = false;
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('paymentModeForm', {
    templateUrl: payment_mode_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope) {
        get_form_data_url = typeof($routeParams.id) == 'undefined' ? laravel_routes['getPaymentModeFormData'] : laravel_routes['getPaymentModeFormData'] + '/' + $routeParams.id;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        $http({
            url: laravel_routes['getPaymentModeFormData'],
            method: 'GET',
            params: {
                'id': typeof($routeParams.id) == 'undefined' ? null : $routeParams.id,
            }
        }).then(function(response) {
            self.payment_mode = response.data.payment_mode;
            self.action = response.data.action;
            $rootScope.loading = false;
            if (self.action == 'Edit') {
                if (self.payment_mode.deleted_at) {
                    self.switch_value = 'Inactive';
                } else {
                    self.switch_value = 'Active';
                }
            } else {
                self.switch_value = 'Active';
            }
        });

        var form_id = '#form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'question': {
                    required: true,
                    minlength: 3,
                    maxlength: 255,
                },
                'answer': {
                    required: true,
                    minlength: 3,
                    maxlength: 255,
                },
            },
            invalidHandler: function(event, validator) {
                checkAllTabNoty()
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('#submit').button('loading');
                $.ajax({
                        url: laravel_routes['savePaymentMode'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        if (res.success == true) {
                            custom_noty('success', res.message)
                            $location.path('/payment-mode-pkg/payment-mode/list');
                            $scope.$apply();
                        } else {
                            if (!res.success == true) {
                                $('#submit').button('reset');
                                showErrorNoty(res)
                            } else {
                                $('#submit').button('reset');
                                $location.path('/payment-mode-pkg/payment-mode/list');
                                $scope.$apply();
                            }
                        }
                    })
                    .fail(function(xhr) {
                        $('#submit').button('reset');
                        showServerErrorNoty()
                    });
            }
        });
    }
});