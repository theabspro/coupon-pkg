app.component('couponCodes', {
    templateUrl: coupon_codes_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $location) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        var table = $('#coupon_code_table').DataTable({
            "dom": dom_structure,
            info: true,
            "language": {
                "search": "",
                "lengthMenu": "Rows Per Page _MENU_",
                "searchPlaceholder": "Search",
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
                url: laravel_routes['getCouponList'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    // d.start_date = $('#start_date').val();
                    // d.end_date = $('#end_date').val();
                },
            },
            columns: [
                { data: 'action', class: 'action', searchable: false },
                { data: 'coupons_count', name: 'coupons_count', searchable: false },
                { data: 'printed_date', name: 'coupons.date', searchable: false },
                { data: 'uploaded_by', name: 'mpay_employee_details.employee_name', searchable: false },
                { data: 'uploaded_date', name: 'coupons.created_at', searchable: false },
            ],
            "infoCallback": function(settings, start, end, max, total, pre) {
                $('#table_info').html(max)
            },
            "initComplete": function(settings, json) {
                $('.dataTables_length select').select2();
            },
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        /* Page Title Appended */
        $('.page-header-content .display-inline-block .data-table-title').html('Coupon Codes <span class="badge badge-secondary" id="table_info">0</span>');
        $('.page-header-content .search.display-inline-block .add_close_button').html('<button type="button" class="btn btn-img btn-add-close"><img src="' + image_scr2 + '" class="img-responsive"></button>');
        $('.page-header-content .refresh.display-inline-block').html('<button type="button" class="btn btn-refresh"><img src="' + image_scr3 + '" class="img-responsive"></button>');

        var addnew_block = $('#add_new_wrap').html();
        $('.page-header-content .alignment-right .add_new_button').html('<a role="button" id="open" data-toggle="modal"  data-target="#coupon-code-filter" class="btn btn-img"> <img src="' + image_scr + '" alt="Filter" onmouseover="this.src=' + image_scr1 + '" onmouseout="this.src=' + image_scr + '"></a>' +
            addnew_block);

        $('.btn-add-close').on("click", function() {
            $('#coupon_code_table').DataTable().search('').draw();
        });

        $('.btn-refresh').on("click", function() {
            $('#coupon_code_table').DataTable().ajax.reload();
        });

        $rootScope.loading = false;
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('couponView', {
    templateUrl: coupon_codes_view_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        $http.get(
            coupon_code_view_url + '/' + $routeParams.id
        ).then(function(response) {
            // console.log(response);
            self.coupon_code = response.data.coupon_code;
            $rootScope.loading = false;
        });

        /* Tab Funtion */
        $('.btn-nxt').on("click", function() {
            $('.editDetails-tabs li.active').next().children('a').trigger("click");
            tabPaneFooter();
        });
        $('.btn-prev').on("click", function() {
            $('.editDetails-tabs li.active').prev().children('a').trigger("click");
            tabPaneFooter();
        });
    }
});