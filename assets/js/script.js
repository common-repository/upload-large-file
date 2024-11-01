jQuery(document).ready(function(){
  jQuery(".accept_feedback").click(function(){
    alert("Thank you for your Feedback, it has been noted.");
  });
});

jQuery(document).ready(function(){
  jQuery(".let_us_know").click(function(){
	existingdiv1 = document.getElementById( "let_us_know_form" );
	jQuery(".let_us_know_form").css("display","block");
	jQuery( ".form_show" ).html( existingdiv1 );
  })
});