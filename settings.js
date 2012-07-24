  

    jQuery(document).ready(function(){
        jQuery.noConflict();
                
            jQuery("#datafields").css("display","none");
    
    jQuery("#include").click(function(){

        // If checked
        if (this.checked)
        {
            //show the hidden div
            jQuery("#datafields").show("fast");
        }
        else
        {
            //otherwise, hide it
            jQuery("#datafields").hide("fast");
        }
      });       
            
            });