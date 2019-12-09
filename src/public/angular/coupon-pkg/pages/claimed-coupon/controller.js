app.component('warrantyPolicyList', {
    templateUrl: warranty_ploicy_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $location) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        var dataTable = $('#claimed_coupon').DataTable({
            stateSave: true,
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
            processing: true,
            serverSide: true,
            paging: true,
            ajax: {
                url: laravel_routes['getWarrantyPolicyList'],
                type: "GET",
                dataType: "json",
                data: function(d) {},
            },
            columns: [
                { data: 'action', class: 'action', searchable: false },
                { data: 'name', name: 'warranty_policies.name' },
                { data: 'status', searchable: false },
            ],
            "initComplete": function(settings, json) {
                $('.dataTables_length select').select2();
            },
            "infoCallback": function(settings, start, end, max, total, pre) {
                $('#table_info').html(total)
            },
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }

        });
        /* Page Title Appended */
        $('.page-header-content .display-inline-block .data-table-title').html('Warranty Policy List <span class="badge badge-secondary" id="table_info">0</span>');
        $('.page-header-content .search.display-inline-block .add_close_button').html('<button type="button" class="btn btn-img btn-add-close"><img src="' + image_scr2 + '" class="img-responsive"></button>');
        $('.page-header-content .refresh.display-inline-block').html('<button type="button" class="btn btn-refresh"><img src="' + image_scr3 + '" class="img-responsive"></button>');

        $('.btn-add-close').on("click", function() {
            $('#claimed_coupon').DataTable().search('').draw();
        });

        $('.btn-refresh').on("click", function() {
            $('#claimed_coupon').DataTable().ajax.reload();
        });

        $scope.deleteWarrantyPolicy = function($id) {
            $('#claimed_coupon_id').val($id);
        }
        $scope.deleteConfirm = function() {
            $id = $('#claimed_coupon_id').val();
            $http.get(
                warranty_ploicy_delete_data_url + '/' + $id,
            ).then(function(response) {
                if (response.data.success) {
                    $noty = new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: 'Claimed Coupon Deleted Successfully',
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 3000);
                    $('#claimed_coupon').DataTable().ajax.reload(function(json) {});
                    $location.path('/coupon-pkg/claimed-coupons/');
                }
            });
        }
        $rootScope.loading = false;
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------