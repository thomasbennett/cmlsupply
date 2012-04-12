<?php
 /**
 * GoMage.com
 *
 * GoMage Feed Pro
 *
 * @category     Extension
 * @copyright    Copyright (c) 2010 GoMage.com (http://www.gomage.com)
 * @author       GoMage.com
 * @license      http://www.gomage.com/licensing  Single domain license
 * @terms of use http://www.gomage.com/terms-of-use
 * @version      Release: 1.1
 * @since        Class available since Release 1.0
 */

class GoMage_Feed_Model_Item extends Mage_Core_Model_Abstract
{
	
	protected $_productCollection;
	protected $_categoryCollection;
	protected $_parentProductsCache = array();
	
    public function _construct()
    {
        parent::_construct();
        $this->_init('gomage_feed/item');
    }
    
    public function getCategoriesCollection(){
    	
    	if(is_null($this->_categoryCollection)){
    	
    		$this->_categoryCollection	= Mage::getResourceModel('catalog/category_collection')->addAttributeToSelect('name');
    	
    	}
    	
    	return $this->_categoryCollection;
    	
    }
    
    public function getParentProduct(Varien_Object $product, $collection = null){
    	
    	if (!isset($this->_parentProductsCache[$product->getEntityId()])){
    		
    		$connection = Mage::getSingleton('core/resource')->getConnection('read');
	    	$table = Mage::getSingleton('core/resource')->getTableName('catalog_product_relation');
	    	
	    	$parent_product = null;
	    	
    		$parent_id = $connection->fetchOne('SELECT `parent_id` FROM '.$table.' WHERE `child_id` = '.intval($product->getEntityId()));
    		
    		if($parent_id > 0){
	    		
	    		if($collection){
	    		
	    			$parent_product = $collection->getItemById($parent_id);
	    		
	    		}
				
				if(!$parent_product){
					
					$parent_product = Mage::getModel('catalog/product')->load($parent_id);
					
				}
				
				$this->_parentProductsCache[$product->getEntityId()] = $parent_product;
				
			}else{
				
				$this->_parentProductsCache[$product->getEntityId()] = new Varien_Object();
				
			}
    		
    	
    	}
    	
    	return $this->_parentProductsCache[$product->getEntityId()];
    }
    
    public function getRootCategory()
    {       
        $category = Mage::getModel('catalog/category')->load(Mage::app()->getStore()->getRootCategoryId());               
        return $category;
    } 
    
    public function getProductsCollection($filterData = '', $current_page = 0, $length = 50){
    	
    	if (is_null($this->_productCollection) && $this->getId()){
						
			$collection = Mage::getModel('gomage_feed/product_collection')->addAttributeToSelect('*');                           
                                                           
			$collection->addStoreFilter(Mage::app()->getStore()); 						
			
			if($length != 0){			
			   $collection->setPage($current_page+1, $length);			
			}
			
			$collection->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
                        ->addMinimalPrice()
                        ->addFinalPrice()
                        ->addTaxPercents()
                        ->addUrlRewrite($this->getRootCategory()->getId());
                        
            if($this->getUseLayer()){
                Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);
            }    
						
			if($this->getUseDisabled()){
			   Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
			   $collection->addAttributeToFilter('status', array('in'=>Mage_Catalog_Model_Product_Status::STATUS_ENABLED));
			}
			
			if($this->getFilter()){
				
				$filter_values = json_decode($this->getFilter(), true);
				
				foreach((array)$filter_values as $filter){
					
				    if (trim(@$filter['attribute_code']) == 'qty')
				    {
				        $collection->joinField('qty',
                                    'cataloginventory/stock_item',
                                    'qty',
                                    'product_id=entity_id',
                                    '{{table}}.stock_id=1',
                                    'left');
				    }				    
					$code 		= trim(@$filter['attribute_code']);
					$condition	= trim(@$filter['condition']);
					$value		= trim(@$filter['value']);
					
					if($code && $condition && $value){
						
					    if ($code == 'category_id')
					        $collection->addFeedCategoryFilter($condition, $value);					        
					    else
						    $collection->addAttributeToFilter($code, array($condition=>$value));
					}
					
				}
				
			}
			
			$this->_productCollection = $collection;
		
		}
		
		return $this->_productCollection;
    }
    
    public function save(){
    	if(!$this->getFilename()){
    		
    		$this->setFilename(preg_replace('/[^\w\d]/', '-', trim(strtolower($this->getName()))).'.'.$this->getType());
    		
    	}
    	if(strpos($this->getFilename(), '.') === false){
    		$this->setFilename($this->getFilename().'.'.$this->getType());
    	}
    	
    	if($id = Mage::getModel('gomage_feed/item')->load($this->getFilename(), 'filename')->getId()){
    		
    		if($id != $this->getId()){
    		
				throw new Mage_Core_Exception(Mage::helper('gomage_feed')->__('Filename "%s" exists', $this->getFilename()));
				
			}
			
		}
    	
    	return parent::save();
    }
    
    public function getDirPath(){
    	return sprintf('%s/productsfeed', Mage::getBaseDir('media'));
    }
    
    
    public function getTempFilePath(){
    	$filename = 'feed-gen-data-'.$this->getId().'.tmp';
    	return sprintf('%s/productsfeed/%s', Mage::getBaseDir('media'), $filename);
    }
    
    public function writeTempFile($start = 0, $length = 50, $filename = ''){
    	
    	if($filename){
    		$filePath	= sprintf('%s/productsfeed/%s', Mage::getBaseDir('media'), $filename);
    	}else{
    		$filePath	= $this->getTempFilePath();
    	}
    	$fileDir	= sprintf('%s/productsfeed', Mage::getBaseDir('media'));
    	
    	
    	if(!file_exists($fileDir)){
    		
    		mkdir($fileDir);
    		
    		chmod($fileDir, 0777);
    		
    	}
    	
    	if(is_dir($fileDir)){
    		
    		if($this->getType() == 'csv'){
    			
    			switch($this->getDelimiter()):
    				case('comma'):default:
    					$delimiter = ",";
    				break;
    				case('tab'):
    					$delimiter = "\t";
    				break;
    				case('colon'):
    					$delimiter = ":";
    				break;
    				case('space'):
    					$delimiter = " ";
    				break;
    				case('vertical pipe'):
    					$delimiter = "|";
    				break;
    				case('semi-colon'):
    					$delimiter = ";";
    				break;
    			endswitch;
    			
    			switch($this->getEnclosure()):
    				
    				case(1): default:
    					
    					$enclosure = "'";
    					
    				break;
    				
    				case(2):
    					
    					$enclosure = '"';
    					
    				break;
    				
    				case(3):
    					
    					$enclosure = ' ';
    					
    				break;
    				
    			endswitch;
    			
    			//$enclosure = $this->getEnclosure() == 1 ? "'" : '"';
    			
    			$collection = $this->getProductsCollection('', $start, $length);
    			
    			$stock_collection = Mage::getResourceModel('cataloginventory/stock_item_collection');
    			$maping = json_decode($this->getContent());
    			
    			$fp = fopen($filePath, 'a');
    			
    			$codes = array();
				
				foreach($maping as $col){
					
					if($col->type == 'attribute'){
					
						$codes[] = $col->attribute_value;
					
					}
					
				}
				
				$custom_attributes = Mage::getResourceModel('gomage_feed/custom_attribute_collection');
    			$custom_attributes->load();
    			
    			foreach($custom_attributes as $_attribute){
    				
    				$options = Zend_Json::decode($_attribute->getData('data'));
    				
    				if($options && is_array($options)){
	    				
	    				$_attribute->setOptions($options);
	    				
	    				foreach($options as $option){
		    				
		    				foreach($option['condition'] as $_condition){
		    					$codes[] = $_condition['attribute_code'];
		    				}
	    				
	    				}
	    				
    				}else{
    					$_attribute->setOptions(array());
    				}
    				
    			}
    			
    			$attributes = Mage::getModel('eav/entity_attribute')
		            	->getCollection()
		            	//->setItemObjectClass('catalog/resource_eav_attribute')
		            	->setEntityTypeFilter(Mage::getResourceModel('catalog/product')->getEntityType()->getData('entity_type_id'))
		            	->setCodeFilter($codes);
		        
				$log_fp = fopen(sprintf('%s/productsfeed/%s', Mage::getBaseDir('media'), 'log-'.$this->getId().'.txt'), 'a');
 				fwrite($log_fp, date("F j, Y, g:i:s a").', page:'.$start.', items selected:'.count($collection)."\n");
				fclose($log_fp);
    			
    			$store = Mage::getModel('core/store')->load($this->getStoreId());
    			    			
    			
    			foreach($collection as $product){
    				
    				$fields = array();
    				
    				$category = null;
					
					foreach($product->getCategoryIds() as $id){
						
						$_category = $this->getCategoriesCollection()->getItemById($id);
						
						if(is_null($category) || $category && $_category && $category->getLevel() < $_category->getLevel()){
							
							$category = $_category;
							
						}
						
					}
					
					if($category){
						
						$product->setCategory($category->getName());
						$product->setCategoryId($category->getEntityId());
					
					}
    									    				
    				foreach($maping as $col){
    					
    					$value = null;
    					
    					if($col->type == 'attribute'){
    						
    						if($col->attribute_value):
    						
    						
    						switch($col->attribute_value):
    							    						
    						    case ('price'):
    								
    						        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_GROUPED )
    						           $value =  $store->convertPrice($product->getMinimalPrice(), false, false);    						           
    						        else 
    								   $value = $store->convertPrice($product->getPrice(), false, false);
    								
    							break;
    								
    							case ('store_price'):
    								
    								$value = $store->convertPrice($product->getFinalPrice(), false, false);
    								
    							break;
    							
    							
    							case ('parent_url'):
    								
    								if(($parent_product = $this->getParentProduct($product, $collection)) && $parent_product->getEntityId() > 0){
    									
										$value = $parent_product->getProductUrl(false);
										
										break;
										
    								}
    								
    								$value = $product->getProductUrl(false);
    								
    							break;
    							
    							case('image'):
    							case('gallery'):
    							case('media_gallery'):

    							   if (!$product->hasData('product_base_image'))
                                   {                                                                                                                                                
                                       $_prod = Mage::getModel('catalog/product')->load($product->getId());

                                       try{		    						        		    						    		    						     
    										$image_url = (string)Mage::helper('catalog/image')->init($_prod, 'image');
    										
    									}catch(Exception $e){    										
    										$image_url = '';    										
    								   }                                                                              
                                       $product->setData('product_base_image', $image_url);
                                       $value = $image_url;
                                          
                                   }
                                   else 
                                   {                                                                                           
                                       $value = $product->getData('product_base_image');
                                   }        							    		    						
																		
    							break;
    							case('image_2'):    							   
    							case('image_3'):
    							case('image_4'):        
    							case('image_5'):    							                                                                                                                                  
                                       if (!$product->hasData('media_gallery_images'))
                                       {
                                           $_prod = Mage::getModel('catalog/product')->load($product->getId());
                                           $product->setData('media_gallery_images', $_prod->getMediaGalleryImages());    
                                       }
                                       $i = 0;                                       
                                       foreach ($product->getMediaGalleryImages() as $_image)
                                       {
                                           $i++;
                                           if (('image_' . $i) == $col->attribute_value) $value = $_image['url'];
                                       } 																		
    							break;
    							case('url'):
    								$value = $product->getProductUrl(false);
    							break;
    							case('qty'):
    								
    								if($stock_item = $stock_collection->getItemByColumnValue('product_id', $product->getId())){
    								
    									$value = ceil($stock_collection->getItemByColumnValue('product_id', $product->getId())->getQty());
    								
    								}else{
    								
    									$value = 0;
    								
    								}
    							break;
    							case('category'):
    								
    								$category = null;
									
									foreach($product->getCategoryIds() as $id){
										
										$_category = $this->getCategoriesCollection()->getItemById($id);
										
										if(is_null($category) || $category && $_category && $category->getLevel() < $_category->getLevel()){
											
											$category = $_category;
											
										}
										
									}
									
									if($category){
									
										$value = $category->getName();
									
									}
									
    							break;
    							default:
    								
    								if(strpos($col->attribute_value, 'custom:') === 0){
    									
    									$custom_code = trim(str_replace('custom:', '', $col->attribute_value));
    									
    									if($custom_attribute = $custom_attributes->getItemByColumnValue('code', $custom_code)){
	    									
	    									$options = $custom_attribute->getOptions();
	    									
	    									foreach($options as $option){
	    										
	    										foreach($option['condition'] as $condition){
	    											
	    										    	    										    
	    											switch($condition['attribute_code']):    							    							
    							
                            							case ('store_price'):
                            								
                            								$attr_value = $store->convertPrice($product->getFinalPrice(), false, false);
                            								
                            							break;
                            							
                            							
                            							case ('parent_url'):
                            								
                            								if(($parent_product = $this->getParentProduct($product, $collection)) && $parent_product->getEntityId() > 0){
                            									
                        										$attr_value = $parent_product->getProductUrl(false);
                        										
                        										break;
                        										
                            								}
                            								
                            								$attr_value = $product->getProductUrl(false);
                            								
                            							break;
                            							
                            							case('image'):
                            							case('gallery'):
                            							case('media_gallery'):
                        
                            							   if (!$product->hasData('product_base_image'))
                                                           {                                                                                                                                                
                                                               $_prod = Mage::getModel('catalog/product')->load($product->getId());
                        
                                                               try{		    						        		    						    		    						     
                            										$image_url = (string)Mage::helper('catalog/image')->init($_prod, 'image');
                            										
                            									}catch(Exception $e){    										
                            										$image_url = '';    										
                            								   }                                                                              
                                                               $product->setData('product_base_image', $image_url);
                                                               $attr_value = $image_url;
                                                                  
                                                           }
                                                           else 
                                                           {                                                                                           
                                                               $attr_value = $product->getData('product_base_image');
                                                           }        							    		    						
                        																		
                            							break;
                            							case('url'):
                            								$attr_value = $product->getProductUrl(false);
                            							break;
                            							case('qty'):
                            								
                            								if($stock_item = $stock_collection->getItemByColumnValue('product_id', $product->getId())){
                            								
                            									$attr_value = ceil($stock_collection->getItemByColumnValue('product_id', $product->getId())->getQty());
                            								
                            								}else{
                            								
                            									$attr_value = 0;
                            								
                            								}
                            							break;
                            							case('category'):
                            								
                            								$category = null;
                        									
                        									foreach($product->getCategoryIds() as $id){
                        										
                        										$_category = $this->getCategoriesCollection()->getItemById($id);
                        										
                        										if(is_null($category) || $category && $_category && $category->getLevel() < $_category->getLevel()){
                        											
                        											$category = $_category;
                        											
                        										}
                        										
                        									}
                        									
                        									if($category){
                        									
                        										$attr_value = $category->getName();
                        									
                        									}                        									
                            							break;
                            							case('category_id'):
                            							        $attr_value = $product->getCategoryIds();                            							        
                            							break;    
                            							default:	    									    
	    										                $attr_value = $product->getData($condition['attribute_code']);
	    										    endswitch;
	    											
	    											
	    											$cond_value = $condition['value'];
	    												    											
	    											
	    											$is_multi = false;
	    											
	    											if($product_attribute = $attributes->getItemByColumnValue('attribute_code', $condition['attribute_code'])){
														
														if($product_attribute->getFrontendInput() == 'multiselect'){
															
															$is_multi = true;
															$attr_value = explode(',', $attr_value);
														
														}
														
													}
													
													if ($condition['attribute_code'] == 'category_id')
													{
													    $is_multi = true;
													}
													
													
													
	    											switch($condition['condition']):
	    												
	    												case('eq'):
	    													
		    												if(!$is_multi && $attr_value == $cond_value || $is_multi && in_array($cond_value, $attr_value)){
		    													
		    													continue 2;
		    													
		    												}else{
		    													
		    													continue 3;
		    													
		    												}
	    												
	    												break;
	    												
	    												case('neq'):
	    												
		    												if(!$is_multi && $attr_value != $cond_value || $is_multi && !in_array($cond_value, $attr_value)){
		    													
		    													continue 2;
		    													
		    												}else{
		    													
		    													continue 3;
		    													
		    												}
	    												
	    												break;
	    												
	    												case('gt'):
	    												
		    												if($attr_value > $cond_value){
		    													
		    													continue 2;
		    													
		    												}else{
		    													
		    													continue 3;
		    													
		    												}
	    												
	    												break;
	    												
	    												case('lt'):
	    												
		    												if($attr_value < $cond_value){
		    													
		    													continue 1;
		    													
		    												}else{
		    													
		    													continue 3;
		    													
		    												}
	    												
	    												break;
	    												
	    												case('gteq'):
	    												
		    												if($attr_value >= $cond_value){
		    													
		    													continue 2;
		    													
		    												}else{
		    													
		    													continue 3;
		    													
		    												}
	    												
	    												break;
	    												
	    												case('lteq'):
	    												
		    												if($attr_value <= $cond_value){
		    													
		    													continue 2;
		    													
		    												}else{
		    													
		    													continue 3;
		    													
		    												}
	    												
	    												break;
	    												
	    												case('like'):
	    												
		    												if(strpos($attr_value, $cond_value) !== false){
		    													
		    													continue 2;
		    													
		    												}else{
		    													
		    													continue 3;
		    													
		    												}
	    												
	    												break;
	    												
	    												case('nlike'):
	    												
		    												if(strpos($attr_value, $cond_value) === false){
		    													
		    													continue 2;
		    													
		    												}else{
		    													
		    													continue 3;
		    													
		    												}
	    												
	    												break;
	    												
	    											endswitch;
	    											
	    										
	    										}
	    										
	    										if($option['value_type'] == 'percent'){
	    											
	    											$value = floatval($product->getData($option['value_type_attribute']))/100*floatval($option['value']);
	    											
	    										}else{
	    											
	    											$value = $option['value'];
	    											
	    										}
	    										
	    										break;
	    										
	    									}
	    									
	    									if($value === null && $custom_attribute->getDefaultValue()){
	    										
	    									    switch($custom_attribute->getDefaultValue()):    							    							
    							
                            							case ('store_price'):
                            								
                            								$value = $store->convertPrice($product->getFinalPrice(), false, false);
                            								
                            							break;
                            							
                            							
                            							case ('parent_url'):
                            								
                            								if(($parent_product = $this->getParentProduct($product, $collection)) && $parent_product->getEntityId() > 0){
                            									
                        										$value = $parent_product->getProductUrl(false);
                        										
                        										break;
                        										
                            								}
                            								
                            								$value = $product->getProductUrl(false);
                            								
                            							break;
                            							
                            							case('image'):
                            							case('gallery'):
                            							case('media_gallery'):
                        
                            							   if (!$product->hasData('product_base_image'))
                                                           {                                                                                                                                                
                                                               $_prod = Mage::getModel('catalog/product')->load($product->getId());
                        
                                                               try{		    						        		    						    		    						     
                            										$image_url = (string)Mage::helper('catalog/image')->init($_prod, 'image');
                            										
                            									}catch(Exception $e){    										
                            										$image_url = '';    										
                            								   }                                                                              
                                                               $product->setData('product_base_image', $image_url);
                                                               $value = $image_url;
                                                                  
                                                           }
                                                           else 
                                                           {                                                                                           
                                                               $value = $product->getData('product_base_image');
                                                           }        							    		    						
                        																		
                            							break;
                            							case('url'):
                            								$value = $product->getProductUrl(false);
                            							break;
                            							case('qty'):
                            								
                            								if($stock_item = $stock_collection->getItemByColumnValue('product_id', $product->getId())){
                            								
                            									$value = ceil($stock_collection->getItemByColumnValue('product_id', $product->getId())->getQty());
                            								
                            								}else{
                            								
                            									$value = 0;
                            								
                            								}
                            							break;
                            							case('category'):
                            								
                            								$category = null;
                        									
                        									foreach($product->getCategoryIds() as $id){
                        										
                        										$_category = $this->getCategoriesCollection()->getItemById($id);
                        										
                        										if(is_null($category) || $category && $_category && $category->getLevel() < $_category->getLevel()){
                        											
                        											$category = $_category;
                        											
                        										}
                        										
                        									}
                        									
                        									if($category){
                        									
                        										$value = $category->getName();
                        									
                        									}
                        									
                            							break;
                            							default:	    									    
	    										            $value = $product->getData($custom_attribute->getDefaultValue());
	    										  endswitch;          
	    										
	    									}
	    									
    									}
    									
    									
    									
    								}elseif($attribute = $attributes->getItemByColumnValue('attribute_code', $col->attribute_value)){
    									
    									if($attribute->getFrontendInput() == 'select' || $attribute->getFrontendInput() == 'multiselect'){
    										
    										$value = implode(', ', (array)$product->getAttributeText($col->attribute_value));

    									}
    									/*
    									elseif($attribute->getFrontendInput() == 'gallery'){
    									        									    
                                            $value = '';
                                                                                        
                                            $_num = intval(preg_replace('/[^\d]+/', '', $col->name));                                                                                        
                                                                                                                                    
                                            if ($_num)
                                            {
                                               if (!$product->hasData('media_gallery_images'))
                                               {                                                                                                                                                
                                                   $_prod = Mage::getModel('catalog/product')->load($product->getId());                                            
                                                   $_gallery = $_prod->getMediaGallery('images');
                                                   if (is_array($_gallery) && count($_gallery))
                                                      $product->setData('media_gallery_images', $_gallery);
                                                   else
                                                      $product->setData('media_gallery_images', new Varien_Data_Collection());   
                                               }
                                               else 
                                               {                                                   
                                                   $_gallery = $product->getData('media_gallery_images');
                                               }    
                                                                                              
                                               
                                               if (is_array($_gallery) && count($_gallery))
                                               {
                                                  if (isset($_gallery[$_num]))
                                                  {                                                     
                                                     $file_url = $_gallery[$_num]['file'];
                                                     if ($file_url)
                                                     {                                                        
                                                        try{
                    										$value = (string)Mage::helper('catalog/image')->init($product, 'gallery', $file_url);
                    										
                    									}catch(Exception $e){
                    										
                    										$value = '';                    										
                    									}
                                                        
                                                     }                                                              
                                                  }
                                               }
                                                                                               
                                            }                                                                                                                                        									    
    										
    									}*/
    									else{
    										
    										$value = $product->getData($col->attribute_value);
    										
    									}
    								
    								}else{
    									
										$value = $product->getData($col->attribute_value);
										
									}
									
    							break;
    						endswitch;
    						
    						else:
    							
    							$value = '';
    							
    						endif;
							
							
							
							if($output_type = $col->output_type){
								switch(trim($output_type)){
				    				case 'float':
				    					
				    					//$value = number_format($value, 2);
				    					$value = number_format($value, 2, '.', '');

				    				break;
				    				
				    				case 'int':
				    					
				    					$value = intval($value);
				    					
				    				break;
				    				
				    				case 'striptags':
				    					
				    					$value = strip_tags($value);
				    					
				    				break;
				    				
				    				case 'htmlspecialchars':
                                        				    				    				    				    
				    				    $encoding = mb_detect_encoding($value);
                                        $value = mb_convert_encoding($value, "UTF-8", $encoding);				    				    				    				                      				    				        				    				              
                                        $value = htmlentities($value, null, "UTF-8");
                                        				    					
				    				break;
				    				
				    				case 'htmlspecialchars_decode':				    					

				    				    $value = htmlspecialchars_decode($value);
				    					
				    				break;
				    				
				    			}
			    			}
			    						    			

			    			
    					}else{
    						$value = $col->prefix.$col->static_value.$col->sufix;
    					}
    					if(intval($this->getRemoveLb())){
    						$value = str_replace("\n", '', $value);
    						$value = str_replace("\r", '', $value);
    					}
    					$fields[] = $col->prefix.$value.$col->sufix;
    					
    				}
    				
    				if ($enclosure != ' ')
    				    fputcsv($fp, $fields, $delimiter, $enclosure);
    				else
    				    $this->myfputcsv($fp, $fields, $delimiter);    
    				
    			}
    			
    			fclose($fp);
    			
    		}else{
    			
	    		$rootBlock = Mage::getModel('gomage_feed/item_block', array('content'=>$this->getContent(), 'feed'=>$this));
    			file_put_contents($filePath, $rootBlock->render());
    			
    		}
    	
    	}
    	
    }
    
    public function generate(){
        
        if (Mage::getStoreConfig('gomage_feedpro/configuration/enable'))
        {
            if (Mage::getStoreConfig('gomage_feedpro/configuration/memory_limit'))
            {
                 ini_set("memory_limit", Mage::getStoreConfig('gomage_feedpro/configuration/memory_limit') . "M");
            }
            if (Mage::getStoreConfig('gomage_feedpro/configuration/upload_max_filesize'))
            {
                 ini_set("upload_max_filesize", Mage::getStoreConfig('gomage_feedpro/configuration/upload_max_filesize') . "M");
            }
            if (Mage::getStoreConfig('gomage_feedpro/configuration/post_max_size'))
            {
                 ini_set("post_max_size", Mage::getStoreConfig('gomage_feedpro/configuration/post_max_size') . "M");
            }
        }
            	
		$fileDir	= sprintf('%s/productsfeed', Mage::getBaseDir('media'));
    	$filePath	= sprintf('%s/productsfeed/%s', Mage::getBaseDir('media'), $this->getFilename());
    	
    	if(!file_exists($fileDir)){
    		
    		mkdir($fileDir);
    		
    		chmod($fileDir, 0777);
    		
    	}
    	
    	if(is_dir($fileDir)){
    		
    		if($this->getType() == 'csv'){
    			
    			switch($this->getDelimiter()):
    				case('comma'):default:
    					$delimiter = ",";
    				break;
    				case('tab'):
    					$delimiter = "\t";
    				break;
    				case('colon'):
    					$delimiter = ":";
    				break;
    				case('space'):
    					$delimiter = " ";
    				break;
    				case('vertical pipe'):
    					$delimiter = "|";
    				break;
    				case('semi-colon'):
    					$delimiter = ";";
    				break;
    			endswitch;
    			//$delimiter = $this->getDelimiter();
    			
    			switch($this->getEnclosure()):
    				
    				case(1): default:
    					
    					$enclosure = "'";
    					
    				break;
    				
    				case(2):
    					
    					$enclosure = '"';
    					
    				break;
    				
    				case(3):
    					
    					$enclosure = ' ';
    					
    				break;
    				
    			endswitch;
    			
    			//$enclosure = $this->getEnclosure() == 1 ? "'" : '"';
    			
    			$collection = $this->getProductsCollection();
    			$total_products = $collection->getSize();
    			$stock_collection = Mage::getResourceModel('cataloginventory/stock_item_collection');
    			$maping = json_decode($this->getContent());
    			
    			$fp = fopen($filePath, 'w');
    			
    			if($this->getShowHeaders()){
    				
    				$fields = array();
    				
    				foreach($maping as $col){
    					
    					$fields[] = $col->name;
    					
    				}
    				
    				
    				fputcsv($fp, $fields, $delimiter, $enclosure);
    			}
    			
    			$per_page = intval($this->getIterationLimit());
    			
    			if($per_page){
    				
    				$pages = ceil($total_products/$per_page);
    				
    			}else{
    				
    				$pages = 1;
    				$per_page = 0;
    			}
    			
    			file_put_contents($fileDir.'/log-'.$this->getId().'.txt', "started at:".date("F j, Y, g:i:s a").", pages:{$pages}, per_page:{$per_page} \n");

    			                      			
    			for($i = 0;$i<$pages;$i++){

					if ($_fp = fopen(Mage::getModel('core/store')->load($this->getStoreId())->getUrl('feed/index/index', array('id'=>$this->getId(), 'start'=>$i, 'length'=>$per_page, '_nosid' => true)), 'r')){

						$contents = '';
						while (!feof($_fp)) {
						  $contents .= fread($_fp, 8192);
						}
			            
			            $response = Zend_Json::decode($contents);
			            
			            if(!isset($response['result']) || !$response['result']){
			            	
			            	throw new Mage_Core_Exception(Mage::helper('gomage_feed')->__('There was an error while generating file. Change "Number of Products" in the Advanced Settings.
                                                Try to change "Number of Products" in the Advanced Settings.
                                                For example: set "Number of Products" equal 50 or 100.'));
			            	
			            }
			            
			        }else{
			        	
			        	throw new Mage_Core_Exception(Mage::helper('gomage_feed')->__('Cant open temp file'));
			        	
			        }
			        
			        fclose($_fp);
			        
		        }
		        		        
    			    					        		        		        		        
    			if($csv_data = @file_get_contents($this->getTempFilePath())){
	    			
	    			fwrite($fp, $csv_data);
	    			
    			}
                if (file_exists($this->getTempFilePath()))
                {
        			unlink($this->getTempFilePath());
                }
    			fclose($fp);
    			
    		}else{
    			
	    		$rootBlock = Mage::getModel('gomage_feed/item_block', array('content'=>$this->getContent(), 'feed'=>$this));
    			file_put_contents($filePath, $rootBlock->render());
    			
    		}
    	
    	}
    	
    	$this->setData('generated_at', date('Y-m-j H:i:s', time()));
    	$this->save();
    	
    	
    	
    }
    public function ftpUpload(){
    	
    	if(intval($this->getFtpActive())){
    		
    		$host_info = explode(':', $this->getFtpHost());
    		
    		$host = $host_info[0];
    		$port = 21;
    		
    		if(isset($host_info[1])){
    			$port = intval($host_info[1]);
    		}
    		
    		if($connection = ftp_connect($host, $port)){
    			
    			try{
    				
    				$ligun_result = ftp_login($connection, $this->getFtpUserName(), $this->getFtpUserPass());
    				
    			}catch(Exception $e){
            	
            		$ligun_result = false;
            	
            	}
            	
    			if($ligun_result){
    				
    				
    				if($this->getFtpPassiveMode()){
    					ftp_pasv($connection, true);
    				}else{
    					ftp_pasv($connection, false);
    				}
    				
    				if(ftp_chdir($connection, $this->getFtpDir())){
    					
    					
    					$filePath	= sprintf('%s/productsfeed/%s', Mage::getBaseDir('media'), $this->getFilename());
    					
    					
    					if(ftp_put($connection, $this->getFilename(), $filePath, FTP_BINARY)){
    						
    						$this->setData('uploaded_at', date('Y-m-j H:i:s', time()));
					    	$this->save();
					    	
    						
    						return true;
    						
    					}else{
    						
    						throw new Mage_Core_Exception('Cannot upload file.');
    						
    					}
    					
    				}else{
    					throw new Mage_Core_Exception('Cannot change dir.');
    				}
    				
    			}else{
    				throw new Mage_Core_Exception('Authenticate failure.');
    			}
    			
    		}else{
    			
    			throw new Mage_Core_Exception('Can’t connect to host.');
    			
    		}
    		
    	}
    	
    	return false;
    	
    }
    
    public function getUrl(){
    	
    	$file_path = sprintf('productsfeed/%s', $this->getFilename());
    	
    	if(file_exists(Mage::getBaseDir('media').'/'.$file_path)){
    	
    		return Mage::getBaseUrl('media', false).$file_path;
    	
    	}
    	
    	return '';
    }
    
    public function delete(){
    	
    	if($this->getFilename()){
    		
    		$fileDir	= sprintf('%s/productsfeed', Mage::getBaseDir('media'));
    		$filePath	= sprintf('%s/productsfeed/%s', Mage::getBaseDir('media'), $this->getFilename());
    		
    		@unlink($filePath);
    		
    	}
    	
    	return parent::delete();
    }
    
    public function myfputcsv( $fp, $fields, $delimiter = ';', $enclosure = ' ' )
    {        
        for( $i = 0; $i < sizeof( $fields ); $i++ )
        {        
            $use_enclosure = false;
            if ( strpos( $fields[$i], $delimiter ) !== false )
            {
                $use_enclosure = true;
            }
            if ( strpos( $fields[$i], $enclosure ) !== false )
            {
                $use_enclosure = true;
            }
            if ( strpos( $fields[$i], "\\" ) !== false )
            {
                $use_enclosure = true;
            }
            if ( strpos( $fields[$i], "\n" ) !== false )
            {
                $use_enclosure = true;
            }
            if ( strpos( $fields[$i], "\r" ) !== false )
            {
                $use_enclosure = true;
            }
            if ( strpos( $fields[$i], "\t" ) !== false )
            {
                $use_enclosure = true;
            }
            if ( strpos( $fields[$i], " " ) !== false )
            {
                $use_enclosure = true;
            }
    
            if ( $use_enclosure == true )
            {
                $fields[$i] = explode( "\$enclosure", $fields[$i] );
                for( $j = 0; $j < sizeof( $fields[$i] ); $j++ )
                {
                    $fields[$i][$j] = explode( $enclosure, $fields[$i][$j] );
                    $fields[$i][$j] = implode( "{$enclosure}{$enclosure}", $fields[$i][$j] );
                }
                $fields[$i] = implode( "\$enclosure", $fields[$i] );
                $fields[$i] = "{$fields[$i]}";
            }
        }
            
        return fwrite( $fp, implode( $delimiter, $fields ) . "\n" );
    }
    
}


