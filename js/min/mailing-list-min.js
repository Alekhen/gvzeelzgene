$("#mailing-list-form").on("submit",function(a){a.preventDefault;var i={action:"save",email:$("#mailing-list-form-email").val()};console.log(i),$.ajax({url:$("#mailing-list-form").data("api"),method:"POST",data:i,error:function(){alert("An Error Has Occured")},success:function(a){alert(a.message)}})});