var banner_image_index;
$(function() {
    var e = window.location.href;
    $("a.main_menu_head").each(function(a) {
        $(this).removeClass("a_sel");
        $(this).attr("id", a);
        var i = $(this).attr("href");
        e == i && ($(this).removeClass("a_sel").addClass("a_sel"), $(this).mouseover(function() {
            $(this).addClass("a_sel")
        }), $(this).mouseout(function() {
            $(this).addClass("a_sel")
        }))
    })
}), $(document).ready(function() {
		
		
		
    "articles" == window.location.pathname.split("/")[1] && jQuery("#1").addClass("a_sel"), "politics" == window.location.pathname.split("/")[1] && jQuery("#2").addClass("a_sel"), "movies" == window.location.pathname.split("/")[1] && jQuery("#3").addClass("a_sel"), "movie-gossip" == window.location.pathname.split("/")[2] && (jQuery("#4").addClass("a_sel"), jQuery("#3").removeClass("a_sel")), "reviews" == window.location.pathname.split("/")[2] && (jQuery("#5").addClass("a_sel"), jQuery("#3").removeClass("a_sel")), "http://gallery.greatandhra.com/index.php" == window.location.pathname && jQuery("#7").addClass("a_sel");
    var e = $(window).width();
    var h = $(window).height();
    $(".source-image-left").append('<div class="close_button">[X] Close</div>'), $(".source-image-right").append('<div class="close_button">[X] Close</div>'), $padding = (e - "1002") / 2, $(".source-image-left").show(), $(".source-image-left").css("left", $padding - 150 + "px"), $(window).bind("resize", function() {
        var e = $(window).width();
		
		var w = $(window).width();
		//alert("Width: " + w);
		var h = $(window).height();
		//alert("Height: " + h);
	    if(w >= 1342 && h >= 600) { 
			$(".source-image-right").show();
			$(".source-image-left").show();
		} else {  
			//alert("no"); 
			$(".source-image-right").hide();
			$(".source-image-left").hide();
		}
		
		
		//alert(e);
        $padding = (e - "1002") / 2, $(".source-image-left").css("left", $padding - 150 + "px")
    }), $padding = (e - "1002") / 2, $(".source-image-right").show(), $(".source-image-right").css("right", $padding - 150 + "px"), $(window).bind("resize", function() {
        var e = $(window).width();
        $padding = (e - "1002") / 2, $(".source-image-right").css("right", $padding - 150 + "px")
    }), $(".close_button").click(function() {
        $(".source-image-left").hide(), $(".source-image-right").hide()
		
    }) //var w = $(window).width();
		//alert(w);
        //w >= 1302 && h >= 600 ? : $(".source-image-right").hide()
		
		
});