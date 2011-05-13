var dsidx_w = {};

dsidx_w.searchWidget = (function () {
	var $ = jQuery;
	
    function isLocationValid() {
    	var valid = false;
    	$('.idx-q-Location-Filter :selected').each(function(index) {
    		if($(this).val().length)
    			valid = true;
    	});
    	return valid;
    }   
    
    function isFieldShown() {
    	var returnStr = "";
    	$('.idx-q-Location-Filter').each( function() {
    		returnStr += $("label[for=" + $(this).attr('id') + "]").text() + ", ";
    	});
    	return returnStr.substring(0, returnStr.length - 2);
    }
    
    var returnObj = {
    	validate: function () {
            if (!isLocationValid())
            {
            	$("#idx-search-invalid-msg").text("Please select at least one of the following fields: " + isFieldShown());
            	return false;
            }
            else
            	return true;
        }
    };
    
    return returnObj;
})();