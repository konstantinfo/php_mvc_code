<section class="contaner">
    
    <section class="innerpage">

        <div class="breadcream"> 
            You are here:  
            <?php            
            echo $this->Html->addCrumb($h1_tag,  '/products/index/'.$category_slug,array('class'=>"active")) ;
            echo $this->Html->getCrumbs(' » ', 'Home');
            ?>
        </div>
        
        <section class="aboutus">
            
            <h2><?php echo $h1_tag; ?></h2>

            <?php echo $page_content; ?>

            <div class="rings">
                
                <div class="metal">
                    <h3>Metal Color</h3>
                    <div class="whitebtn"> 
                        <?php
                        $pagelink = '/';

                        if(!empty($sort_by))
                        {
                            $pagelink .= "sort_by:".$sort_by.'/';
                        }
                        
                        if(!empty($this->params['paging']['Product']['page']))
                        {
                            $pagelink .= "page:".$this->params['paging']['Product']['page'];
                        }                            
                            
                        $class = null;
                        if($metal == 'yellow')
                        {
                            $class = array('class'=>'active');
                        }
                        echo $this->Html->link("Yellow", array('controller'=>'products','action'=>'index/'.$category_slug.$pagelink),$class);
                        ?> 
                        <?php 
                        $class = null;
                        if($metal == 'white')
                        {
                            $class = array('class'=>'active');
                        }
                        echo $this->Html->link("White", array('controller'=>'products','action'=>'index/'.$category_slug.'/metal:white'.$pagelink),$class);
                        ?> 
                    </div>
                    <div class="floatright"> 
                        
                        <?php echo $this->Form->create("Products",array("action"=>"index/".$separator,"method"=>"Post",'id'=>'product_sort'));?>
                        
                        <div class="selectmenu">
                            <p>Sort by : </p> 
                            <div class="selectBox PadT7" id="selectBoxProduct">
                                <span style="width:107px;" class="selected"></span>
                                <span class="selectArrow"> </span>
                                <div style="width: 123px; margin: 7px 0pt 0pt; display: none;" class="selectOptions">
                                    <span id="Product.view desc" class="selectOption">Most Popular</span>
                                    <span id="Product.product_price asc" class="selectOption">Lowest Price</span>
                                    <span id="Product.product_price desc" class="selectOption">Highest Price</span>
                                </div>
                                <?php
                                if($sort_by == 'Product.product_price asc')
                                    $sort_id = 'Lowest Price';
                                elseif($sort_by == 'Product.product_price desc')
                                    $sort_id = 'Highest Price';
                                else
                                    $sort_id = 'Most Popular';
                                ?>
                                
                                <input type="hidden" id="<?php echo $sort_id; ?>" value="<?php echo $sort_by;?>" name="data[Product][sort_by]" />
                                
                            </div>
                        </div>
                             
                        <?php echo $this->Form->end(); ?>

                    </div>
                </div>

                <div class="comentsblock">
                    
                    <?php
                    if(isset($products) && !empty($products))
                    {
                        foreach ($products as $product) {
                            $product_colors = $this->requestAction(array('controller' => 'product_attribute_options', 'action' => 'get_attribute_options', $product['Product']['id'], '10'));
                            if(isset($product_colors['0']))
                            {
                            ?>
                                <input type="hidden" name="slider_product_id_<?php echo $product['Product']['id']; ?>" id="slider_product_id_<?php echo $product['Product']['id']; ?>" value="<?php echo $product['Product']['id']; ?>" />
                                <input type="hidden" name="slider_product_color_<?php echo $product['Product']['id']; ?>" id="slider_product_color_<?php echo $product['Product']['id']; ?>" value="<?php echo $product_colors['0']['Attribute']['slug']; ?>" />
                            <?php
                            }
                        }
                    }
                    
                    if(isset($products) && !empty($products))
                    {                        
                        $this->Paginator->options(array('url'=>array('controller'=>'products','action'=>'index/'.$separator.'/sort_by:'.$sort_by)));
                        
                        foreach($products as $product)
                        {
                            $product_colors = $this->requestAction(array('controller' => 'product_attribute_options', 'action' => 'get_attribute_options',$product['Product']['id'],'10'));
                    ?>                  
                            <div class="productring">
                                <div class="productlist2"></div>
                                <h2><a class="jatt_tooltip {width:500px;}" title="<?php echo htmlspecialchars($product['Product']['product_description']);?>"><?php echo $product['Product']['product_name'];?></a></h2>
                                <p>Starting at $<?php echo $product['Product']['product_price'];?></p>
                                <div class="productringimage" id="main_productimage<?php echo $product['Product']['id'];?>"> 
                                    <?php 
                                    echo $this->Html->image(Configure::read('Site.url').'/image.php?image=uploads/products/'.$product['Product']['id'].'/'.$metal.'/Top_'.$product_colors['0']['Attribute']['slug'].'_'.$product_colors['0']['Attribute']['slug'].'.jpg&width=155', array('escape' => false, "class"=>"jatt_tooltip {width:auto;padding:3px;}", 'title' => '<img src='.Configure::read('Site.url').'/image.php?image=uploads/products/'.$product['Product']['id'].'/'.$metal.'/Top_'.$product_colors['0']['Attribute']['slug'].'_'.$product_colors['0']['Attribute']['slug'].'.jpg&width=350>'));
                                    ?> 
                                </div>
                                <div class="Personalize2"><a href="javascript:void(0);" onclick="ring_configurator('<?php echo $product['Product']['id'];?>','<?php echo $metal; ?>');">Personalize <span></span> </a></div>
                                
                                <div class="ColorSwatches">
                                    <ul>
                                        <?php
                                        foreach($product_colors as $product_color)
                                        {
                                        ?>
                                            <li><a href="javascript:product_img_change('<?php echo $product['Product']['id'];?>','<?php echo $metal; ?>','Top','<?php echo $product_color['Attribute']['slug'];?>','<?php echo $product_color['Attribute']['slug'];?>');" style="background:<?php echo "#".$product_color['Attribute']['color_code'];?>" title="<?php echo $product_color['Attribute']['name'];?>" class="jatt_tooltip {width:auto;}"><?php echo $product_color['Attribute']['name'];?></a></li>
                                        <?php
					}
                                        ?>
                                    </ul>
                                </div>
                            </div>                    
                        <?php
                        }
			?>
                    
                    <div class="clear"></div>
                    
                    <div class="Pagging PadT7"> 
                        <?php echo $this->Paginator->prev('Prev', null, null, array('escape' => false,'class' => 'disabled')); ?>
                        <?php echo $this->Paginator->numbers(array('separator'=>'')); ?>
                        <?php echo $this->Paginator->next("Next", array('class'=>false), null, array('escape' => false,'class' => 'disabled')); ?>
                    </div> 
                    
                    <?php
                    }
                    else
                    {
                    ?>
                        <div class="record_not_found">No Record Found!!</div>
                    <?php                   
                    }
                    ?>
                    
                </div>

            </div>

        </section>

    </section>
    
</section>

<script type='text/javascript'><!--
    
    $(document).ready(function() {
    	enableSelectBoxProduct();    	
    });    

    function enableSelectBoxProduct(){
        $('div#selectBoxProduct').each(function(){

            $(this).children('span.selected').html($(this).children('input').attr("id"));

            $(this).children('span.selected,span.selectArrow').click(function(){
                if($(this).parent().children('div.selectOptions').css('display') == 'none'){
                        $(this).parent().children('div.selectOptions').css('display','block');
                }
                else
                {
                        $(this).parent().children('div.selectOptions').css('display','none');
                }
            });

            $(this).find('span.selectOption').click(function(){
                $(this).parent().css('display','none');
                $(this).parent().siblings('span.selected').html($(this).html());

                var record_value = $(this).attr("id");
                var record_name = $(this).html();

                $(this).parent().next('input').attr('id', record_name);
                $(this).parent().next('input').val(record_value);

                $('#product_sort').submit();
            });
            
            $("body").click
            (
                function(e)
                {
                    var arr = [ "selectBox", "selected", "selectArrow", "selectOptions" ];
                    
                    if($.inArray(e.target.className, arr)==-1)
                    {
                        $('div.selectOptions').css('display','none');
                    }
                }
            );
                
        });				
    }//-->
        
    function ring_configurator(product_id,metal)
    {    
        var product_color = jQuery('#slider_product_color_'+product_id).val();
        jQuery.fn.colorbox({open:true,onComplete:function() { jQuery('#horiz_container_outer').horizontalScroll(); jQuery('#scrollbar1').tinyscrollbar(); },onClosed:function(){ location.reload(); },width:"901px",top:"20px",href:"<?php echo Configure::read('Site.url').'/products/ring_configurator/'; ?>"+product_id+'/'+metal+'/'+product_color});
        return false;
    }
    
    function product_img_change(product_id,metal,ring_side,color,option)
    {
        var product_img_path;
        var product_img_title;
        
        product_img_path = '<?php echo Configure::read('Site.url').'/image.php?image=uploads/products/'; ?>'+product_id+'/'+metal+'/'+ring_side+'_'+color+'_'+option+'.jpg&width=155';
        product_img_title = '<?php echo Configure::read('Site.url').'/image.php?image=uploads/products/'; ?>'+product_id+'/'+metal+'/'+ring_side+'_'+color+'_'+option+'.jpg&width=350';

        jQuery('#main_productimage'+product_id).children('img').attr('src',product_img_path);
        jQuery('#main_productimage'+product_id).children('img').attr('title','<img src='+product_img_title+' />');

        jQuery('#slider_product_id_'+product_id).val(product_id);
        jQuery('#slider_product_color_'+product_id).val(color);
    }

</script>