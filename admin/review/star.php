<?php $link="review/"; ?>
<div class="appRating col-xs-12">
    <style>
        .star-rating { 
            margin-top: 9px;
            margin-bottom: -9px;
        }
        .star-rating .star-value{
            width: 0%;
        }
    </style>
    <link rel="stylesheet" href="<?php echo $link; ?>star.css?v=1">

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <div class="wp_rate" style="display:none;"> 
        <a href="#" class="rating-closebt close" onclick="hideDivRate()">×</a>
        <span>If you enjoy <?php echo $appName ?> App, would you mind taking a moment to rate it? It won't take more than a minute. Thanks for your support! </span>
        <div id="rater"></div>  
    </div>
    <script src="<?php echo $link; ?>lib_star.js"></script>
    <script type="text/javascript"> 
            const app_name = '<?php echo $app_settings->app_name ?>'; 
            function showDivReview(){ 
                if(getCookie(`${app_name}`) == null || getCookie(`${app_name}`) == 1){
                    $('.wp_rate').show();
                }
            }
            function onload(event) {
                var myDataService = {
                    rate: function (rating) {
                        return {then: function (callback) {
                                setTimeout(function () {
                                    callback((Math.random() * 5));
                                }, 1000);
                            }
                        }
                    }
                }
                var starRating1 = raterJs({
                    starSize: 32,
                    element: document.querySelector("#rater"),
                    rateCallback: function rateCallback(rating, done) {
                        this.setRating(rating);
                        done();
                        setCookie(`${app_name}`,0,10)
                        var star_value = $("#rater").attr("data-rating");
                        if (star_value < 4) {
                            improveStar(star_value);
                        } else {
                            gooodStar(star_value);
                        }
                    }
                });
            }
            window.addEventListener("load", onload, false);
            function gooodStar(star_value) {
                $(".goodStar").show();
            }
            function improveStar(star_value) {
                $(".improveStar").show();
            }
            function hidenDiv() {
                $(".improveStar").hide();
                $(".goodStar").hide();
            }
            function hidenDivRespone() {
                $(".respone").hide();
            }
            function sendReview() {
                $(".improveStar").hide();
                var star_value = $("#rater").attr("data-rating");
                var shop = $('.shopName').text();
                var comment = $("#comment").val();
                var data = {action: "sendReview", comment: comment, shop: shop, star_value: star_value}
                $.ajax({
                    url: '<?php echo $link; ?>sendComment.php',
                    type: 'GET',
                    data: data
                }).done((res) => {
                    $(".improveStarRespone").show();
                    $(".wp_rate").hide();
                })
            }
            function hideDivRate() {
                $(".wp_rate").hide();
            }
            function writeReview() { 
                $(".wp_rate").hide();
                var star_value = $("#rater").attr("data-rating");
                var shop = $('.shopName').text();
                var data = {action: "sendReview", shop: shop, star_value: star_value}
                $.ajax({
                    url: '<?php echo $link; ?>sendComment.php',
                    type: 'GET',
                    data: data
                }).done((res) => {
                     $(".writeRiewRespone").show(); 
                })
            }
            function setCookie(name, value, days) {
                var expires = "";
                if (days) {
                    var date = new Date();
                    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                    expires = "; expires=" + date.toUTCString();
                }
                document.cookie = name + "=" + (value || "") + expires + "; path=/";
            }
            function getCookie(name) {
                var nameEQ = name + "=";
                var ca = document.cookie.split(';');
                for (var i = 0; i < ca.length; i++) {
                    var c = ca[i];
                    while (c.charAt(0) == ' ')
                        c = c.substring(1, c.length);
                    if (c.indexOf(nameEQ) == 0)
                        return c.substring(nameEQ.length, c.length);
                }
                return null;
            }
    </script>
    <script>
        if(typeof $ == 'undefined'){ 
        javascript: (function(e, s) {
            e.src = s;
            e.onload = function() {
                $ = jQuery.noConflict(); 
                showDivReview();
            };
            document.head.appendChild(e);
        })(document.createElement('script'), 'https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js') 
        }else{ 
            showDivReview();
        }

    </script>
    <div class="improveStar" style="display:none;">
        <div role="dialog" id="moda_rating" tabindex="0" class="bootbox modal fade  in" style="display: block; padding-right: 15px; background: #00000047;">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" style="color: #b57b06">How can we improve?</h4>
                    </div>
                    <div class="modal-body">
                        <div class="bootbox-body" style="margin-bottom: 20px">Let us know
                            how we could be doing better. Your feedback is important to
                            improve <strong><?php echo $appName ?></strong> app, and we appreciate
                            your time to leave us a comment.</div>
                        <textarea class="form-control" cols="30" rows="10"  placeholder="Leave a review" id="comment" name="rate_comment"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button id="submit-rating" onclick="sendReview()" class="btn btn-info btn-block" value="improve" type="submit" data-bb-handler="main">
                            Submit <i class="fa fa-fw fa-heart"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="goodStar" style="display:none;">
        <div  role="dialog" onclick="hidenDiv()" id="moda_rating_good" tabindex="-1" class="bootbox modal fade in" style="display: block; padding-right: 15px;  background: #00000047;">
            <div class="modal-dialog" style="text-align: left;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" style="color: #b57b06">Thanks you for rating!</h4>
                    </div>
                    <div class="modal-body">
                        <div class="bootbox-body" style="margin-bottom: 20px">We're so happy that you like <strong><?php echo $appName ?></strong> app and gave it a high rate. Could you please spend 2 minutes leaving us a good review on Shopify App Store? Your review means a lot to us.</div>
                    </div>
                    <div class="modal-footer">
                        <a class="btn btn-info btn-block" onclick="writeReview()" target="_blank" type="button" href="<?php echo $linkApp ?>" data-bb-handler="main" id="goodreview">
                            Write a quick Review! <i class="fa fa-fw fa-thumbs-o-up faa-bounce animated"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="respone"> 
        <div class="improveStarRespone" style="display: none;"> 
            <button aria-hidden="true" onclick="hidenDivRespone()" data-dismiss="modal" class="bootbox-close-button close" type="button">×</button>
            <p>Thank you for feedback. We will build the best possible version of <strong><?php echo $appName ?></strong>.</p>
        </div> 
        <div class="writeRiewRespone" style="display: none;"> 
            <button aria-hidden="true" onclick="hidenDivRespone()" data-dismiss="modal" class="bootbox-close-button close" type="button">×</button>
            <p>Thank you for review.</p>
        </div> 
    </div>
</div>

