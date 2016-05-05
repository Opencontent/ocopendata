(function ($, window, document, undefined) {
    "use strict";
    var pluginName = "opendataDataTable",
        defaults = {
            "builder": {
                "query": null,
                "filters": {}
            },
            "table": {
                "id": 'exemple',
                "template": '<table class="table table-striped table-bordered" cellspacing="0" width="100%"></table>'
            },
            "datatable": {
                "deferRender": true,
                "responsive": true,
                "processing": true,
                "serverSide": true,
                "ajax": {
                    serverSide: true
                },
                "columnDefs": [
                    {
                        "render": function (data, type, row, meta) {
                            var validDate = moment(data, moment.ISO_8601);
                            if (validDate.isValid()) {
                                return '<span style="white-space:nowrap">' + validDate.format("D MMMM YYYY, hh:mm") + '</span>';
                            } else {
                                return data;
                            }
                        },
                        "targets": '_all'
                    }
                ],
                "columns": []
            }
        };

    // The actual plugin constructor
    function OpendataDataTable(element, options) {
        this.element = element;
        this.settings = $.extend(true, {}, defaults, options);
        this._defaults = defaults;
        this._name = pluginName;
        this._ajaxUrl = this.settings.datatable.ajax.url;
        this.datatable = null;
        this.init();
    }

    $.extend(OpendataDataTable.prototype, {
        init: function () {
            //this.loadDataTable();
        },
        loadDataTable: function () {
            this.settings.datatable.prevQuery = this.settings.datatable.ajax.query;
            var buildedQuery = this.buildQuery();
            this.settings.datatable.ajax.url = this._ajaxUrl + buildedQuery;
            this.settings.datatable.ajax.query = buildedQuery;
            var id = this.settings.table.id;
            var table = $(this.settings.table.template).attr('id', id);
            $(this.element).append(table);
            if (this.datatable != null) {
                this.datatable.destroy(true);
            }
            this.datatable = table.DataTable(this.settings.datatable);
        },
        buildQuery: function () {
            var query = '';
            $.each(this.settings.builder.filters, function () {
                if (this != null) {
                    if ($.isArray(this.value)) {
                        query += this.field + " " + this.operator + " ['" + this.value.join("','") + "']";
                        query += ' and ';
                    }
                }
            });
            query += this.settings.builder.query;
            //console.log( ' -- Query: ' + query);
            return encodeURIComponent(query);
        }
    });

    $.fn[pluginName] = function (options) {
        return this.each(function () {
            if (!$.data(this, pluginName)) {
                $.data(this, pluginName, new OpendataDataTable(this, options));
            }
        });
    };

})(jQuery, window, document);

var opendataDataTableRenderField = function opendataDataTableRenderField(dataType,
                                                                         templateType,
                                                                         currentLanguage,
                                                                         data,
                                                                         type,
                                                                         row,
                                                                         meta,
                                                                         link) {
    switch (templateType) {
        case 'array of id or remoteId or file or image':
            var names = [];
            if (data.length > 0) {
                $.each(data, function () {
                    names.push(this.name[currentLanguage]);
                });
            }
            data = names.join(', ');
            break;

        case 'file':
            if (data.url) {
                return '<a href="' + data.url + '">' + data.filename + '</a>';
            }
            break;

        case 'multifile':
            var files = [];
            if (data.length > 0) {
                $.each(data, function () {
                    files.push('<a href="' + this.url + '">' + this.filename + '</a>');
                });
            }
            return files.join(', ');
            break;

        case 'array of objects':
            var authors = [];
            if (data.length > 0) {
                $.each(data, function () {
                    authors.push('<a href="mailto:' + this.email + '">' + this.name + '</a>');
                });
            }
            return authors.join(', ');
            break;

        case 'user':
            var value = [];
            if (data.length > 0) {
                $.each(data, function () {
                    value.push('<a href="mailto:' + this.email + '">' + this.login + '</a>');
                });
            }
            return value.join(', ');
            break;

        case 'ISO 8601 date':
            var validDate = moment(data);
            if (validDate.isValid()) {
                if (dataType == 'ezdate') {
                    data = '<span style="white-space:nowrap">' + validDate.format("D MMMM YYYY") + '</span>';
                } else {
                    data = '<span style="white-space:nowrap">' + validDate.format("D MMMM YYYY, hh:mm") + '</span>';
                }

            }
            break;
    }

    switch (dataType) {
        case 'ezprice':
            if (Query.type(data) == 'string'){
                var number = data.split('|')[0];
                data = parseFloat(number).toFixed(2);
                break;
            }else{
                data = parseFloat(data.value).toFixed(2);
            }

    }

    if (link) {
        data = '<a href="' + link + '">' + data + '</a>'
    }

    return data;

};