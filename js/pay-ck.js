$(function(){function e(e){var t=$("form");if(t.find(".payment-errors").length===0){t.find(".submit").prepend('<strong class="payment-errors"></strong>');$(".payment-errors").hide().slideDown()}t.find(".payment-errors").text(e)}console.log(navigator.userAgent);navigator.userAgent.indexOf("Chrome/")!=-1&&$("input[type=number]").prop("type","text");$("#lookup form").submit(function(e){e.preventDefault();window.location="/"+$("#booking_number").val().toUpperCase()});$("#payment form").submit(function(n){var r=$(this);n.preventDefault();if(!$("#terms").prop("checked")){e("You have to agree to the Terms and Conditions");return}$("body").css("cursor","wait");r.find("button").prop("disabled",!0);Stripe.card.createToken(r,t)});var t=function(t,n){var r=$("form");if(n.error){e(n.error.message);$("body").css("cursor","");r.find("button").prop("disabled",!1)}else{var i=n.id;r.append($('<input type="hidden" name="stripeToken" />').val(i));r.get(0).submit()}}});