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
                { data: 'name', name: 'payment_modes.name', searchable: true },
                { data: 'code', name: 'payment_modes.code', searchable: true },
                { data: 'description', name: 'payment_modes.description', searchable: true },
                { data: 'display_order', name: 'payment_modes.display_order', searchable: false },
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
            '<a href="#!/payment-mode-pkg/payment-mode/add" type="button" class="btn btn-secondary" dusk="add-btn">' +
            'Add Payment Mode' +
            '</a>'
        );

        $('.btn-add-close').on("click", function() {
            $('#payment_modes_list').DataTable().search('').draw();
        });

        $('.btn-refresh').on("click", function() {
            $('#payment_modes_list').DataTable().ajax.reload();
        });

        //DELETE
        $scope.deletePaymentMode = function($id) {
            $('#payment_mode_id').val($id);
        }
        $scope.deleteConfirm = function() {
            $id = $('#payment_mode_id').val();
            $http.get(
                laravel_routes['deletePaymentMode'], {
                    params: {
                        id: $id,
                    }
                }
            ).then(function(response) {
                if (response.data.success) {
                    custom_noty('success', response.data.message);
                    $('#payment_modes_list').DataTable().ajax.reload();
                    $scope.$apply();
                } else {
                    custom_noty('error', response.data.errors);
                }
            });
        }

        //FOR FILTER
        /*$('#payment_mode_code').on('keyup', function() {
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
        }*/

        $rootScope.loading = false;
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('paymentModeForm', {
    templateUrl: payment_mode_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        fileUpload();
        $http({
            url: laravel_routes['getPaymentModeFormData'],
            method: 'GET',
            params: {
                'id': typeof($routeParams.id) == 'undefined' ? null : $routeParams.id,
            }
        }).then(function(response) {
            self.payment_mode = response.data.payment_mode;
            self.attachment = response.data.attachment;
            self.action = response.data.action;
            self.theme = response.data.theme;
            $rootScope.loading = false;
            if (self.action == 'Edit') {
                if (self.payment_mode.deleted_at) {
                    self.switch_value = 'Inactive';
                } else {
                    self.switch_value = 'Active';
                }
                if (self.attachment) {
                    $scope.PreviewImage = 'public/themes/' + self.theme + '/img/payment_mode_logo/' + self.attachment.name;
                    $('#edited_file_name').val(self.attachment.name);
                } else {
                    $('#edited_file_name').val('');
                }
            } else {
                self.switch_value = 'Active';
            }
        });

        $scope.SelectFile = function(e) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $scope.PreviewImage = e.target.result;
                $scope.$apply();
            };
            reader.readAsDataURL(e.target.files[0]);
        };

        var form_id = '#form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'name': {
                    required: true,
                    minlength: 3,
                    maxlength: 191,
                },
                'code': {
                    required: true,
                    minlength: 3,
                    maxlength: 191,
                },
                'description': {
                    required: true,
                    minlength: 3,
                    maxlength: 255,
                },
                'display_order': {
                    required: true,
                    number: true,
                },
                'logo_id': {
                    extension: "jpg|jpeg|png|ico|bmp|svg|gif",
                },
            },
            messages: {
                'logo_id': {
                    extension: "Accept Image Files Only. Eg: jpg,jpeg,png,ico,bmp,svg,gif"
                }
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
                                var errors = '';
                                for (var i in res.errors) {
                                    errors += '<li>' + res.errors[i] + '</li>';
                                }
                                custom_noty('error', errors);
                            } else {
                                $('#submit').button('reset');
                                $location.path('/payment-mode-pkg/payment-mode/list');
                                $scope.$apply();
                            }
                        }
                    })
                    .fail(function(xhr) {
                        $('#submit').button('reset');
                        custom_noty('error', 'Something went wrong at server');
                    });
            }
        });
    }
});