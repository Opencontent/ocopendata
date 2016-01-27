<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>OCQL Console</title>

    <!-- Bootstrap core CSS -->
    <link href="http://getbootstrap.com/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
    <script src="http://code.jquery.com/jquery-1.12.0.min.js" type="application/javascript"></script>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
    <script src="http://code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
    <script src="http://getbootstrap.com/dist/js/bootstrap.min.js"></script>

</head>

<body>

<div class="container">

    <h2>OCQL Console
        <small>beta version</small>
    </h2>

    <form id="search" action="{'opendata/console'|ezurl(no)}" method="GET">
        <div class="row">
            <div class="col-xs-11">
                <input id="query" class="form-control input-lg" type="text"
                       placeholder="q = 'Lorem ipsum' and keywords in [foo, bar] and published range [last month, today]"
                       name="query"
                       value="{$query|wash()}"/>
            </div>
            <div class="col-xs-1">
                <button class="btn btn-success btn-lg" type="submit">
                    <i class="fa fa-search" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </form>

    {if $error}
        <div class="alert alert-warning" role="alert">{$error|wash()}</div>
    {/if}


    <div id="query-string" style="margin: 20px 0"></div>
    <div id="query-analysis" style="margin: 20px 0"></div>
    <div id="search-results" style="margin: 20px 0"></div>

    <hr />
    <h2>Classi</h2>
    <form id="class" action="{'opendata/console'|ezurl(no)}" method="GET">
        <div class="row">
            <div class="col-xs-11">
                <select class="form-control input-lg" name="class">
                    {foreach $classes as $class}
                        <option value="{$class|wash()}">{$class|wash()}</option>
                    {/foreach}
                </select>

            </div>
            <div class="col-xs-1">
                <button class="btn btn-success btn-lg" type="submit">
                    <i class="fa fa-search" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </form>

    <div id="class-result" style="margin: 20px 0"></div>

    <script>
    $(function() {ldelim}
        var analyzer = "{'opendata/analyzer'|ezurl(no,full)}/";
        var endpoint = "{'api/opendata/v2/content/search'|ezurl(no,full)}/";
        var classEndpoint = "{'api/opendata/v2/classes'|ezurl(no,full)}/";
        var availableTokens = [{foreach $tokens as $token}'{$token}'{delimiter},{/delimiter}{/foreach}];
        {literal}
        var $container = $('#search-results');
        var $analysis = $('#query-analysis');
        var $string = $('#query-string');
        var $form = $('form#search');
        var $classForm = $('form#class');
        var $icon = $form.find('button > i');
        var $classFormIcon = $classForm.find('button > i');
        var $classContainer = $('#class-result');
        var search = function( url ){
            var searchQuery = url.replace(endpoint,'');
            $icon.addClass('fa-cog fa-spin');
            $.ajax({
                type: "GET",
                url: url,
                contentType: "application/json; charset=utf-8",
                dataType: "json",
                success: function(data) {
                    if( 'error_message' in data )
                        loadError(data,$container);
                    else {
                        loadSearchResults(data);
                        $('<a href="'+url+'"><small>'+url+'</small></a>').appendTo($string);
                    }
                },
                error: function(data){
                    var error = data.responseJSON;
                    loadError(error,$container);
                }
            });
            $.get(analyzer, {query:searchQuery}, function(data){
                var content = '<h4>Analisi della query</h4>';
                var writeFilter = function(item){
                    content += '<span class="field label label-success" data-toggle="tooltip" data-placement="top" title="Campo">'+item.field+'</span> ';
                    content += '<span class="operator label label-default" data-toggle="tooltip" data-placement="top" title="Operatore">'+item.operator+'</span> ';
                    content += '<span class="value label label-info" data-toggle="tooltip" data-placement="top" title="Valore ('+item.format+')">'+item.value+'</span> ';
                };
                var writeClause = function(item){
                    content += '<span class="clause label label-danger" data-toggle="tooltip" data-placement="top" title="Clausola logica">'+item.value+'</span> ';
                };
                var writeParenthesis = function(item){
                    content += '<span class="parenthesis label label-default" data-toggle="tooltip" data-placement="top" title="Parentesi">'+item.value+'</span> ';
                };
                var writeParameter = function(item){
                    content += '<span class="key label label-warning" data-toggle="tooltip" data-placement="top" title="Parametro">'+item.key+'</span> ';
                    content += '<span class="value label label-info" data-toggle="tooltip" data-placement="top" title="Valore ('+item.format+')">'+item.value+'</span> ';
                };
                $.each(data,function(){
                    if( this.type == 'filter' ) writeFilter(this);
                    else if( this.type == 'clause' ) writeClause(this);
                    else if( this.type == 'parenthesis' ) writeParenthesis(this);
                    else if( this.type == 'parameter' ) writeParameter(this);
                });
                $analysis.html( content );
            });
        };
        var loadError = function(data,container){
            $icon.removeClass('fa-cog fa-spin');
            $classFormIcon.removeClass('fa-cog fa-spin');
            var content = '<div class="alert alert-warning">'+data.error_message+'</div>';
            container.html(content);
        };
        var loadSearchResults = function(data){
            $icon.removeClass('fa-cog fa-spin');
            var results = data.searchHits;
            $string.html( '<h4>Query eseguita</h4><pre>'+data.query+'</pre>' );
            var content = '<h2>Trovati '+data.totalCount+ ' risultati</h2>';
            if ( results.length > 0 ) {
                content += '<h3>Visualizzati ' + results.length + ' risultati ';
                if ( data.nextPageQuery !== null )
                    content += '<a href="#" data-query="'+data.nextPageQuery+'" class="search">(pagina successiva)</a></h3>';
                else
                    content += '</h3>';
                content += '<ul class="list-group">';
                $.each(results, function(){
                    if( this.metadata.classIdentifier != null ) {
                        content += '<li class="list-group-item">';
                        content += '<a href="/content/view/full/'+this.metadata.mainNodeId+'" target="_blank"><strong>';
                        content += this.metadata.name['ita-IT'];
                        content += '</strong></a>';
                        content += ' ['+this.metadata.classIdentifier+']';
                        content += '<br /><small>';
                        var published = new Date(Date.parse(this.metadata.published));
                        var modified = new Date(Date.parse(this.metadata.modified));
                        content += ' Pubblicato il: '+ published.toDateString();
                        content += ' Ultima modifica di: '+ modified.toDateString();
                        content += '</small>';
                    }
                });
                content += '</ul>';
            }
            $container.html(content);
            $('[data-toggle="tooltip"]').tooltip();
        };
        var loadClass = function(data){
            var content = '<table class="table table-striped">';
            content += '<tr>';
            content += '<th>Identificatore</th>';
            content += '<th>Nome</th>';
            content += '<th>Descrizione</th>';
            content += '<th>Datatype</th>';
            content += '</tr>';
            $.each(data.fields, function(){
                if ( this.isSearchable ) {
                    content += '<tr>';
                    content += '<td>' + this.identifier + '</td>';
                    content += '<td>' + $.map(this.name, function(val,index) {return val;}).join(", ") + '</td>';
                    content += '<td>' + $.map(this.description, function(val,index) {return val;}).join(", ") + '</td>';
                    content += '<td>' + this.dataType + '</td>';
                    content += '</tr>';
                }
            });
            content += '</table>';
            $classContainer.html(content);
            $classFormIcon.removeClass('fa-cog fa-spin');
        };
        $(document).on( 'click', 'a.search', function(e){
            search($(e.currentTarget).data('query'));
            e.preventDefault();
        });
        $form.submit( function(e){
            var query = $form.find('input').val();
            search(endpoint+query);
            e.preventDefault();
        });
        var searchClass = function( url ) {
            $classFormIcon.addClass('fa-cog fa-spin');
            $.ajax({
                type: "GET",
                url: url,
                contentType: "application/json; charset=utf-8",
                dataType: "json",
                success: function (data) {
                    if ('error_message' in data)
                        loadError(data,$classContainer);
                    else
                        loadClass(data);
                },
                error: function (data) {
                    var error = data.responseJSON;
                    loadError(error,$classContainer);
                }
            });
        };
        $classForm.submit( function(e){
            var identifier = $classForm.find('select option:selected').val();
            searchClass(classEndpoint+identifier);
            e.preventDefault();
        });

        function split( val ) {
            return val.split( ' ' );
        }
        function extractLast( term ) {
            return split( term ).pop();
        }
        $form.find('input')
            .bind( "keydown", function( event ) {
                if ( event.keyCode === $.ui.keyCode.TAB &&
                        $( this ).autocomplete( "instance" ).menu.active ) {
                    event.preventDefault();
                }
            })
            .autocomplete({
                minLength: 0,
                source: function( request, response ) {
                    response( $.ui.autocomplete.filter(
                            availableTokens, extractLast( request.term ) ) );
                },
                focus: function() {
                    return false;
                },
                select: function( event, ui ) {
                    var terms = split( this.value );
                    terms.pop();
                    terms.push( ui.item.value );
                    terms.push( "" );
                    this.value = terms.join( " " );
                    return false;
                }
            });
        $('[data-toggle="tooltip"]').tooltip();
        {/literal}
    });
    </script>

</div>


</body>
</html>