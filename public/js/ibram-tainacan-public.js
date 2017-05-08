function showIbramSearch( search_term) {
    var search_term = search_term || "";
    show_modal_main();
    $.ajax({
        url: $('#src').val() + '/controllers/advanced_search/advanced_search_controller.php',
        type: 'POST',
        data: {operation: 'open_page', collection_id: $("#collection_id").val(), home_search_term: search_term}
    }).done(function (result) {
        hide_modal_main();
        $('#main_part').hide();
        $('.menu-ibram').hide();
        $('.ibram-home-container').hide();
        $('#configuration').html(result).show();
        $('#configuration').css('margin-top','40px');
        $('#configuration').css('background','#f2f2f2');
        $('.header-navbar').css('background-color','black');
    });
}
