<?php
/**
 * Static content and dummy data generation for Anyora Commerce.
 */

defined( 'ABSPATH' ) || exit;

function anyora_starter_pages() {
	return array(
		'home'    => array( 'title' => 'Home', 'menu' => 'Home', 'content' => '' ),
		'shop'    => array( 'title' => 'Shop', 'menu' => 'Shop', 'content' => '' ),
		'about'   => array( 'title' => 'About', 'menu' => 'About', 'content' => '<div style="background-color: var(--anyora-bg); padding: 40px 20px;">
    <div style="max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: center;">
        <div>
            <div style="color: #6fbdbd; font-size: 12px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 20px; display: flex; align-items: center; gap: 15px;">
                <span style="width: 30px; height: 2px; background: #6fbdbd; display: inline-block;"></span> ABOUT OUR BUSINESS
            </div>
            <h1 style="font-size: 48px; color: var(--anyora-navy); margin: 0 0 20px 0; font-weight: 800; letter-spacing: -2px; line-height: 1;">About Anyora</h1>
            <p style="font-size: 18px; color: var(--anyora-navy); font-weight: 700; margin-bottom: 20px;">
                Anyora is a UK private-label home-storage brand and online retailer operated by Anyora Limited.
            </p>
            <p style="font-size: 16px; color: #555; line-height: 1.7; margin-bottom: 25px;">
                We offer practical storage products for everyday areas of the home, with clear product, delivery and returns information to help customers make informed purchasing decisions.
            </p>
            <div style="display: flex; gap: 15px;">
                <a href="/shop/" class="btn">Shop our products</a>
                <a href="/contact/" class="btn-outline" style="border-radius: 30px; padding: 12px 24px; font-weight: 600; text-decoration: none; border: 2px solid var(--anyora-navy); color: var(--anyora-navy); display: inline-block;">Contact us</a>
            </div>
        </div>
        <div style="position: relative;">
            <img src="/wp-content/themes/anyora-commerce/assets/images/drawer-organizer.png" alt="Drawer Organizer" style="border-radius: 20px; width: 100%; max-height: 380px; object-fit: cover; box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
            <div style="position: absolute; bottom: 20px; right: 20px; background: white; padding: 25px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); max-width: 250px;">
                <div style="font-size: 10px; font-weight: 700; color: #888; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px;">UK PRIVATE-LABEL RETAILER</div>
                <div style="font-size: 24px; font-weight: 800; color: var(--anyora-navy); margin-bottom: 5px;">Anyora</div>
                <div style="font-size: 12px; color: #666;">Operated by Anyora Limited</div>
            </div>
        </div>
    </div>
</div>

<div style="background: white; border-bottom: 1px solid var(--anyora-border); border-top: 1px solid var(--anyora-border);">
    <div style="max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: repeat(4, 1fr); gap: 0;">
        <div style="padding: 40px 20px; border-right: 1px solid var(--anyora-border);">
            <div style="font-size: 10px; font-weight: 700; color: #888; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px;">LEGAL BUSINESS</div>
            <div style="font-size: 16px; font-weight: 700; color: var(--anyora-navy); margin-bottom: 5px;">Anyora Limited</div>
            <div style="font-size: 12px; color: #666;">Registered in England and Wales</div>
        </div>
        <div style="padding: 40px 20px; border-right: 1px solid var(--anyora-border);">
            <div style="font-size: 10px; font-weight: 700; color: #888; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px;">COMPANY NUMBER</div>
            <div style="font-size: 16px; font-weight: 700; color: var(--anyora-navy); margin-bottom: 5px;">16938766</div>
            <div style="font-size: 12px; color: #666;">Incorporated 2 January 2026</div>
        </div>
        <div style="padding: 40px 20px; border-right: 1px solid var(--anyora-border);">
            <div style="font-size: 10px; font-weight: 700; color: #888; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px;">ORDER FULFILMENT</div>
            <div style="font-size: 16px; font-weight: 700; color: var(--anyora-navy); margin-bottom: 5px;">Anyora in Bilston</div>
            <div style="font-size: 12px; color: #666;">Stored, packed and dispatched in the UK</div>
        </div>
        <div style="padding: 40px 20px;">
            <div style="font-size: 10px; font-weight: 700; color: #888; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px;">DELIVERY MARKET</div>
            <div style="font-size: 16px; font-weight: 700; color: var(--anyora-navy); margin-bottom: 5px;">United Kingdom</div>
            <div style="font-size: 12px; color: #666;">3-5 working days normally</div>
        </div>
    </div>
</div>

<div style="background: white; padding: 100px 20px;">
    <div style="max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1.2fr; gap: 80px; align-items: center;">
        <div>
            <img src="/wp-content/themes/anyora-commerce/assets/images/bathroom-shelf.png" alt="Bathroom Shelf" style="border-radius: 20px; width: 100%; box-shadow: 0 20px 40px rgba(0,0,0,0.05);">
        </div>
        <div>
            <div style="color: #6fbdbd; font-size: 12px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 20px; display: flex; align-items: center; gap: 15px;">
                <span style="width: 30px; height: 2px; background: #6fbdbd; display: inline-block;"></span> OUR RANGE AND APPROACH
            </div>
            <h2 style="font-size: 48px; color: var(--anyora-navy); margin: 0 0 30px 0; font-weight: 800; letter-spacing: -1.5px; line-height: 1.1;">Practical storage, clearly presented.</h2>
            <p style="font-size: 18px; color: var(--anyora-navy); font-weight: 700; margin-bottom: 20px; line-height: 1.5;">
                Our range focuses on useful storage products for kitchens, bathrooms, drawers, footwear and other everyday spaces.
            </p>
            <p style="font-size: 16px; color: #666; line-height: 1.7; margin-bottom: 20px;">
                Products sold as Anyora-branded goods form part of our private-label range. These products may be produced for the Anyora brand by selected third-party manufacturers.
            </p>
            <p style="font-size: 16px; color: #666; line-height: 1.7; margin-bottom: 25px;">
                Anyora Limited operates the brand and is the retailer responsible for purchases made through this website. We manage the customer-facing product information, pricing, order administration, delivery arrangements and customer support.
            </p>
            
            <div style="background: var(--anyora-bg); border-radius: 15px; padding: 30px; display: flex; gap: 20px; align-items: flex-start;">
                <div style="color: #dcb37b; font-weight: 700; font-size: 14px; margin-top: 3px;">01</div>
                <div>
                    <h4 style="margin: 0 0 10px 0; font-size: 18px; color: var(--anyora-navy);">Practical selection</h4>
                    <p style="margin: 0; color: #666; font-size: 14px; line-height: 1.6;">Products are selected for useful home organisation and everyday storage.</p>
                </div>
            </div>
        </div>
    </div>
</div>' ),
		'contact' => array( 'title' => 'Contact', 'menu' => 'Contact', 'content' => '<div style="background-color: #f4f5f0; padding: 50px 20px; min-height: 80vh; font-family: var(--font-primary); box-sizing: border-box;">
    <div style="max-width: 1100px; margin: 0 auto; box-sizing: border-box;">
        
        <!-- Header -->
        <div style="margin-bottom: 35px; box-sizing: border-box;">
            <h1 style="font-size: 48px; color: var(--anyora-navy); font-weight: 800; letter-spacing: -1.5px; margin: 0 0 15px 0;">Contact our team</h1>
            <p style="font-size: 16px; color: #555; margin: 0; line-height: 1.6; max-width: 600px;">
                Have a question about an order, shipping, or returns? We\'re here to help. Drop us a line below or reach out via email or phone.
            </p>
        </div>
        
        <div style="display: grid; grid-template-columns: 1.4fr 1fr; gap: 60px; align-items: start; box-sizing: border-box;">
            
            <!-- Contact Form Card -->
            <div style="background: white; padding: 40px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.04); border: 1px solid rgba(0,0,0,0.05); box-sizing: border-box;">
                <h2 style="font-size: 24px; color: var(--anyora-navy); font-weight: 800; margin: 0 0 5px 0; letter-spacing: -0.5px;">Send a message</h2>
                <p style="font-size: 14px; color: #666; margin: 0 0 30px 0;">We typically reply within 24 business hours.</p>
                
                <form action="/wp-admin/admin-post.php" method="POST" style="margin: 0; display: flex; flex-direction: column; gap: 20px; box-sizing: border-box;">
                    <input type="hidden" name="action" value="anyora_contact_form">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; box-sizing: border-box;">
                        <div style="box-sizing: border-box;">
                            <label style="display: block; font-size: 11px; font-weight: 700; color: var(--anyora-navy); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">First Name *</label>
                            <input type="text" name="first_name" required style="width: 100%; height: 48px; padding: 0 16px; border: 1px solid #e2e8f0; border-radius: 8px; font-family: inherit; font-size: 14px; box-sizing: border-box; background: #fff; color: #1a202c; outline: none; transition: border-color 0.2s;">
                        </div>
                        <div style="box-sizing: border-box;">
                            <label style="display: block; font-size: 11px; font-weight: 700; color: var(--anyora-navy); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Last Name *</label>
                            <input type="text" name="last_name" required style="width: 100%; height: 48px; padding: 0 16px; border: 1px solid #e2e8f0; border-radius: 8px; font-family: inherit; font-size: 14px; box-sizing: border-box; background: #fff; color: #1a202c; outline: none; transition: border-color 0.2s;">
                        </div>
                    </div>
                    
                    <div style="box-sizing: border-box;">
                        <label style="display: block; font-size: 11px; font-weight: 700; color: var(--anyora-navy); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Email Address *</label>
                        <input type="email" name="email" required style="width: 100%; height: 48px; padding: 0 16px; border: 1px solid #e2e8f0; border-radius: 8px; font-family: inherit; font-size: 14px; box-sizing: border-box; background: #fff; color: #1a202c; outline: none; transition: border-color 0.2s;">
                    </div>
                    
                    <div style="box-sizing: border-box;">
                        <label style="display: block; font-size: 11px; font-weight: 700; color: var(--anyora-navy); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Order Number (Optional)</label>
                        <input type="text" name="order_number" style="width: 100%; height: 48px; padding: 0 16px; border: 1px solid #e2e8f0; border-radius: 8px; font-family: inherit; font-size: 14px; box-sizing: border-box; background: #fff; color: #1a202c; outline: none; transition: border-color 0.2s;">
                    </div>
                    
                    <div style="box-sizing: border-box;">
                        <label style="display: block; font-size: 11px; font-weight: 700; color: var(--anyora-navy); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Message *</label>
                        <textarea name="message" rows="5" required style="width: 100%; padding: 16px; border: 1px solid #e2e8f0; border-radius: 8px; font-family: inherit; font-size: 14px; box-sizing: border-box; resize: vertical; background: #fff; color: #1a202c; outline: none; transition: border-color 0.2s; line-height: 1.5;"></textarea>
                    </div>
                    
                    <button type="submit" class="btn" style="width: 100%; height: 50px; display: flex; align-items: center; justify-content: center; font-size: 15px; border-radius: 8px; background: var(--anyora-navy); color: white; font-weight: 700; cursor: pointer; border: none; transition: background-color 0.2s; box-shadow: 0 4px 12px rgba(8,29,52,0.15);">Send message</button>
                </form>
            </div>
            
            <!-- Contact Sidebar Info -->
            <div style="display: flex; flex-direction: column; gap: 30px; box-sizing: border-box;">
                
                <div style="box-sizing: border-box;">
                    <h3 style="font-size: 18px; color: var(--anyora-navy); font-weight: 800; margin: 0 0 20px 0; letter-spacing: -0.3px;">Support channels</h3>
                    
                    <!-- Email Card -->
                    <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); border: 1px solid rgba(0,0,0,0.04); display: flex; gap: 15px; align-items: flex-start; margin-bottom: 20px; box-sizing: border-box;">
                        <span style="font-size: 20px; line-height: 1;">✉️</span>
                        <div style="box-sizing: border-box;">
                            <h4 style="margin: 0 0 5px 0; font-size: 15px; color: var(--anyora-navy); font-weight: 700;">Email Support</h4>
                            <p style="margin: 0 0 8px 0; color: #666; font-size: 13px; line-height: 1.4;">Get in touch via email directly.</p>
                            <a href="mailto:support@anyora.uk" style="color: var(--anyora-navy); font-weight: 700; text-decoration: none; font-size: 14px; border-bottom: 1.5px solid var(--anyora-navy); padding-bottom: 2px;">support@anyora.uk</a>
                        </div>
                    </div>
                    
                    <!-- Phone Card -->
                    <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); border: 1px solid rgba(0,0,0,0.04); display: flex; gap: 15px; align-items: flex-start; box-sizing: border-box;">
                        <span style="font-size: 20px; line-height: 1;">📞</span>
                        <div style="box-sizing: border-box;">
                            <h4 style="margin: 0 0 5px 0; font-size: 15px; color: var(--anyora-navy); font-weight: 700;">Phone Support</h4>
                            <p style="margin: 0 0 8px 0; color: #666; font-size: 13px; line-height: 1.4;">Available Monday to Friday, 9am - 5pm.</p>
                            <div style="color: var(--anyora-navy); font-weight: 700; font-size: 14px;">+44 1902 382162</div>
                        </div>
                    </div>
                </div>
                
                <hr style="border: 0; border-top: 1px solid rgba(0,0,0,0.08); margin: 10px 0;">
                
                <div style="box-sizing: border-box;">
                    <h3 style="font-size: 18px; color: var(--anyora-navy); font-weight: 800; margin: 0 0 20px 0; letter-spacing: -0.3px;">Business office</h3>
                    <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); border: 1px solid rgba(0,0,0,0.04); display: flex; gap: 15px; align-items: flex-start; box-sizing: border-box;">
                        <span style="font-size: 20px; line-height: 1;">🏢</span>
                        <div style="box-sizing: border-box;">
                            <h4 style="margin: 0 0 5px 0; font-size: 15px; color: var(--anyora-navy); font-weight: 700;">Registered Address</h4>
                            <p style="margin: 0; color: #666; font-size: 13px; line-height: 1.5;">
                                Anyora Limited<br>
                                72 Ambergate Road, Bilston,<br>
                                WV14 0SR, United Kingdom
                            </p>
                        </div>
                    </div>
                </div>
                
            </div>
            
        </div>
    </div>
</div>
<script>
if (window.location.search.indexOf("success=1") > -1) {
    alert("Thank you! Your message has been sent successfully.");
}
</script>' ),
		'mission' => array( 'title' => 'Mission', 'menu' => 'Mission', 'content' => '<!-- Hero Section --><div style="background-color: var(--anyora-bg); padding: 40px 20px; font-family: var(--font-primary); box-sizing: border-box;"><div style="max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: center; box-sizing: border-box;"><div><div style="color: #6fbdbd; font-size: 12px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 20px; display: flex; align-items: center; gap: 15px;"><span style="width: 30px; height: 2px; background: #6fbdbd; display: inline-block;"></span> OUR MISSION </div><h1 style="font-size: 48px; color: var(--anyora-navy); margin: 0 0 20px 0; font-weight: 800; letter-spacing: -2px; line-height: 1.1;">Our Mission</h1><p style="font-size: 18px; color: var(--anyora-navy); font-weight: 700; margin-bottom: 15px; line-height: 1.5;"> To provide practical storage products selected to help make everyday spaces tidier. </p><p style="font-size: 16px; color: #555; line-height: 1.7; margin-bottom: 30px;"> We aim to reduce clutter and bring harmony to modern living spaces with simple, elegant solutions that are both highly functional and visually pleasing. </p><div style="display: flex; gap: 15px;"><a href="/shop/" class="btn">Explore our range</a></div></div><div><img src="/wp-content/themes/anyora-commerce/assets/images/anyora-prod-shelves.png" alt="Our Mission Shelving" style="border-radius: 20px; width: 100%; max-height: 380px; object-fit: cover; box-shadow: 0 20px 40px rgba(0,0,0,0.1);"></div></div></div><!-- Core Values Section --><div style="background: white; padding: 100px 20px; font-family: var(--font-primary); box-sizing: border-box;"><div style="max-width: 1200px; margin: 0 auto; box-sizing: border-box;"><div style="text-align: center; margin-bottom: 70px; box-sizing: border-box;"><div style="color: #6fbdbd; font-size: 12px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 15px; justify-content: center; display: flex; align-items: center; gap: 10px;"> OUR VALUES </div><h2 style="font-size: 40px; color: var(--anyora-navy); margin: 0; font-weight: 800; letter-spacing: -1px;">What drives Anyora</h2></div><div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 40px; box-sizing: border-box;"><!-- Value 1 --><div style="box-sizing: border-box; text-align: center; padding: 20px;"><div style="font-size: 32px; margin-bottom: 20px;">✨</div><h3 style="font-size: 20px; color: var(--anyora-navy); font-weight: 700; margin: 0 0 12px 0;">Simplicity</h3><p style="margin: 0; color: #666; font-size: 14px; line-height: 1.6;">De-cluttering spaces with intuitive, modular designs. We believe that storage shouldn\'t complicate your life—it should simplify it.</p></div><!-- Value 2 --><div style="box-sizing: border-box; text-align: center; padding: 20px;"><div style="font-size: 32px; margin-bottom: 20px;">🛡️</div><h3 style="font-size: 20px; color: var(--anyora-navy); font-weight: 700; margin: 0 0 12px 0;">Quality Sourcing</h3><p style="margin: 0; color: #666; font-size: 14px; line-height: 1.6;">We select durable, premium materials—from sustainable natural bamboo to thick, food-grade, BPA-free plastics built to endure.</p></div><!-- Value 3 --><div style="box-sizing: border-box; text-align: center; padding: 20px;"><div style="font-size: 32px; margin-bottom: 20px;">🔍</div><h3 style="font-size: 20px; color: var(--anyora-navy); font-weight: 700; margin: 0 0 12px 0;">Practical Utility</h3><p style="margin: 0; color: #666; font-size: 14px; line-height: 1.6;">Every product in our catalog is engineered to fit standard drawers, cupboards, and shelves, providing maximum volumetric efficiency.</p></div></div></div></div><!-- Sourcing Story Section --><div style="background-color: var(--anyora-bg); padding: 100px 20px; font-family: var(--font-primary); box-sizing: border-box;"><div style="max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1.2fr; gap: 80px; align-items: center; box-sizing: border-box;"><div><img src="/wp-content/themes/anyora-commerce/assets/images/anyora-prod-organizer.png" alt="Bamboo Organizer Tray" style="border-radius: 20px; width: 100%; max-height: 380px; object-fit: cover; box-shadow: 0 20px 40px rgba(0,0,0,0.05);"></div><div><div style="color: #6fbdbd; font-size: 12px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 20px; display: flex; align-items: center; gap: 15px;"><span style="width: 30px; height: 2px; background: #6fbdbd; display: inline-block;"></span> SUSTAINABILITY </div><h2 style="font-size: 48px; color: var(--anyora-navy); margin: 0 0 30px 0; font-weight: 800; letter-spacing: -1.5px; line-height: 1.1;">Designed for longevity, sourced with care.</h2><p style="font-size: 18px; color: var(--anyora-navy); font-weight: 700; margin-bottom: 20px; line-height: 1.5;"> We believe that home organization products shouldn\'t just be useful—they should be sustainable. </p><p style="font-size: 16px; color: #666; line-height: 1.7; margin-bottom: 20px;"> By using fast-growing FSC-certified bamboo and high-purity, recyclable polymers, we build storage solutions that stand the test of time while minimizing environmental impact. </p><p style="font-size: 16px; color: #666; line-height: 1.7; margin: 0;"> Our materials are thoroughly checked for chemical safety and toxic additives, ensuring that they are safe for kitchen food storage and kid-friendly playrooms alike. </p></div></div></div>' ),
		'activities' => array( 'title' => 'Activities', 'menu' => 'Activities', 'content' => '<div style="background-color: var(--anyora-bg); padding: 40px 20px; font-family: var(--font-primary); box-sizing: border-box;">
    <div style="max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: center; box-sizing: border-box;">
        <div>
            <div style="color: #6fbdbd; font-size: 12px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 20px; display: flex; align-items: center; gap: 15px;">
                <span style="width: 30px; height: 2px; background: #6fbdbd; display: inline-block;"></span> BUSINESS OPERATIONS
            </div>
            <h1 style="font-size: 48px; color: var(--anyora-navy); margin: 0 0 20px 0; font-weight: 800; letter-spacing: -2px; line-height: 1;">Our Activities</h1>
            <p style="font-size: 18px; color: var(--anyora-navy); font-weight: 700; margin-bottom: 20px;">
                Retail operations, supply chain curation, and nationwide fulfillment.
            </p>
            <p style="font-size: 16px; color: #555; line-height: 1.7; margin-bottom: 0;">
                Anyora Limited operates as a dedicated home organization and storage brand. From design selection to final doorstep delivery, our activities are geared toward making home organization simple, high-quality, and transparent.
            </p>
        </div>
        <div>
            <img src="/wp-content/themes/anyora-commerce/assets/images/logistics-package.png" alt="Logistics & Sourcing" style="border-radius: 20px; width: 100%; max-height: 380px; object-fit: cover; box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
        </div>
    </div>
</div>

<div style="background: white; padding: 100px 20px; box-sizing: border-box;">
    <div style="max-width: 1200px; margin: 0 auto; box-sizing: border-box;">
        
        <div style="text-align: center; margin-bottom: 70px; box-sizing: border-box;">
            <h2 style="font-size: 40px; color: var(--anyora-navy); margin: 0 0 15px 0; font-weight: 800; letter-spacing: -1px;">Principal Business Activities</h2>
            <p style="font-size: 16px; color: #666; max-width: 600px; margin: 0 auto; line-height: 1.6;">How we manage product design, inventory storage, and customer logistics in the UK.</p>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; box-sizing: border-box;">
            
            <!-- Card 1 -->
            <div style="background: var(--anyora-bg); padding: 40px; border-radius: 16px; box-sizing: border-box; display: flex; gap: 20px; align-items: flex-start;">
                <div style="background: white; width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); flex-shrink: 0;">📦</div>
                <div>
                    <h3 style="font-size: 20px; color: var(--anyora-navy); font-weight: 700; margin: 0 0 10px 0;">Private-Label Sourcing</h3>
                    <p style="margin: 0; color: #666; font-size: 14px; line-height: 1.6;">We source and curate practical, durable home storage solutions made from premium materials such as FSC-certified bamboo and durable BPA-free acrylics.</p>
                </div>
            </div>

            <!-- Card 2 -->
            <div style="background: var(--anyora-bg); padding: 40px; border-radius: 16px; box-sizing: border-box; display: flex; gap: 20px; align-items: flex-start;">
                <div style="background: white; width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); flex-shrink: 0;">🔍</div>
                <div>
                    <h3 style="font-size: 20px; color: var(--anyora-navy); font-weight: 700; margin: 0 0 10px 0;">Quality Assurance</h3>
                    <p style="margin: 0; color: #666; font-size: 14px; line-height: 1.6;">Every batch of products undergoes material, durability, and finishing checks to ensure they meet the high standards expected by our customers.</p>
                </div>
            </div>

            <!-- Card 3 -->
            <div style="background: var(--anyora-bg); padding: 40px; border-radius: 16px; box-sizing: border-box; display: flex; gap: 20px; align-items: flex-start;">
                <div style="background: white; width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); flex-shrink: 0;">🏭</div>
                <div>
                    <h3 style="font-size: 20px; color: var(--anyora-navy); font-weight: 700; margin: 0 0 10px 0;">UK Fulfillment Hub</h3>
                    <p style="margin: 0; color: #666; font-size: 14px; line-height: 1.6;">We run our packaging, storage, and order dispatch operations directly from our dedicated facility located in Bilston, United Kingdom.</p>
                </div>
            </div>

            <!-- Card 4 -->
            <div style="background: var(--anyora-bg); padding: 40px; border-radius: 16px; box-sizing: border-box; display: flex; gap: 20px; align-items: flex-start;">
                <div style="background: white; width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); flex-shrink: 0;">🚚</div>
                <div>
                    <h3 style="font-size: 20px; color: var(--anyora-navy); font-weight: 700; margin: 0 0 10px 0;">Logistics & Delivery</h3>
                    <p style="margin: 0; color: #666; font-size: 14px; line-height: 1.6;">Nationwide delivery is handled via reliable UK couriers, normally dispatching within 24 hours and arriving within 3-5 working days.</p>
                </div>
            </div>
            
        </div>
    </div>
</div>' ),
		'terms'   => array( 'title' => 'Terms and conditions', 'menu' => '', 'content' => "<h2>Terms and Conditions</h2>\n<p>These terms and conditions outline the rules and regulations for the use of Anyora's Website.</p>" ),
		'shipping'=> array( 'title' => 'Shipping policy', 'menu' => '', 'content' => "<h2>Shipping Policy</h2>\n<p>We offer free delivery on all orders over £50. Standard shipping takes 3-5 business days.</p>" ),
		'returns' => array( 'title' => 'Returns, refunds and cancellations', 'menu' => '', 'content' => "<h2>Returns & Refunds</h2>\n<p>We offer a 30-day return policy for all unused items in their original packaging.</p>" ),
		'payment' => array( 'title' => 'Payment policy', 'menu' => '', 'content' => "<h2>Payment Policy</h2>\n<p>We accept all major credit cards, PayPal, Apple Pay, and Google Pay. All transactions are securely processed.</p>" ),
		'privacy' => array( 'title' => 'Privacy policy', 'menu' => '', 'content' => "<h2>Privacy Policy</h2>\n<p>Your privacy is important to us. We only collect the necessary information to process your order and provide you with a great experience.</p>" ),
		'cookie'  => array( 'title' => 'Cookie policy', 'menu' => '', 'content' => "<h2>Cookie Policy</h2>\n<p>We use cookies to improve your browsing experience and analyze site traffic.</p>" ),
		'cookie-pref' => array( 'title' => 'Cookie preferences', 'menu' => '', 'content' => "<h2>Cookie Preferences</h2>\n<p>Manage your cookie settings here.</p>" ),
		'my-account' => array( 'title' => 'My account', 'menu' => '', 'content' => "[woocommerce_my_account]" ),
	);
}

function anyora_sideload_theme_image( $filename, $title ) {
    $theme_dir = get_template_directory();
    $file_path = $theme_dir . '/assets/images/' . $filename;
    
    if ( ! file_exists( $file_path ) ) {
        return false;
    }
    
    // Check if attachment already exists in the media library by title/path
    global $wpdb;
    $attachment_id = $wpdb->get_var( $wpdb->prepare(
        "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s",
        '%' . $filename
    ) );
    
    if ( $attachment_id ) {
        return (int) $attachment_id;
    }
    
    // Sideload the image
    require_once( ABSPATH . 'wp-admin/includes/image.php' );
    require_once( ABSPATH . 'wp-admin/includes/file.php' );
    require_once( ABSPATH . 'wp-admin/includes/media.php' );
    
    $upload_dir = wp_upload_dir();
    $target_path = $upload_dir['path'] . '/' . $filename;
    
    // Copy the file
    if ( ! copy( $file_path, $target_path ) ) {
        return false;
    }
    
    $file_type = wp_check_filetype( $filename, null );
    $attachment = array(
        'post_mime_type' => $file_type['type'],
        'post_title'     => sanitize_text_field( $title ),
        'post_content'   => '',
        'post_status'    => 'inherit'
    );
    
    $attach_id = wp_insert_attachment( $attachment, $target_path );
    if ( ! is_wp_error( $attach_id ) ) {
        $attach_data = wp_generate_attachment_metadata( $attach_id, $target_path );
        wp_update_attachment_metadata( $attach_id, $attach_data );
        return (int) $attach_id;
    }
    
    return false;
}

function anyora_create_dummy_products() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    $dummy_categories = array(
        'Kitchen storage' => 'kitchen-storage',
        'Bathroom storage' => 'bathroom-storage',
        'Drawer organisers' => 'drawer-organisers',
        'Shoe storage' => 'shoe-storage',
    );

    foreach ( $dummy_categories as $name => $slug ) {
        if ( ! term_exists( $slug, 'product_cat' ) ) {
            wp_insert_term( $name, 'product_cat', array( 'slug' => $slug ) );
        }
    }

    $dummy_products = array(
        array(
            'title' => 'Compact Mobility Scooter (Foldable)',
            'price' => '899.00',
            'cat'   => 'kitchen-storage',
            'img'   => 'scooter.png',
        ),
        array(
            'title' => 'Bamboo 3-Tier Shelving Unit',
            'price' => '45.00',
            'cat'   => 'kitchen-storage',
            'img'   => 'bamboo-shelf.png',
        ),
        array(
            'title' => 'Minimalist Desk Organizer',
            'price' => '24.99',
            'cat'   => 'drawer-organisers',
            'img'   => 'desk-organizer.png',
        ),
        array(
            'title' => 'Ceramic Bathroom Set',
            'price' => '35.00',
            'cat'   => 'bathroom-storage',
            'img'   => 'bathroom-set.png',
        ),
        array(
            'title' => 'Stackable Bamboo Storage Box',
            'price' => '18.99',
            'cat'   => 'drawer-organisers',
            'img'   => 'anyora-prod-organizer.png',
        ),
        array(
            'title' => 'Premium Bathroom Shelf',
            'price' => '29.99',
            'cat'   => 'bathroom-storage',
            'img'   => 'bathroom-shelf.png',
        ),
    );

    foreach ( $dummy_products as $prod ) {
        $existing = get_page_by_title( $prod['title'], OBJECT, 'product' );
        if ( ! $existing ) {
            $post_id = wp_insert_post( array(
                'post_title'   => $prod['title'],
                'post_content' => 'High quality organization product for your modern home.',
                'post_status'  => 'publish',
                'post_type'    => 'product',
            ) );

            if ( $post_id && ! is_wp_error( $post_id ) ) {
                wp_set_object_terms( $post_id, 'simple', 'product_type' );
                update_post_meta( $post_id, '_visibility', 'visible' );
                update_post_meta( $post_id, '_stock_status', 'instock' );
                update_post_meta( $post_id, '_regular_price', $prod['price'] );
                update_post_meta( $post_id, '_price', $prod['price'] );
                
                // Programmatically upload and attach the featured image
                $attach_id = anyora_sideload_theme_image( $prod['img'], $prod['title'] );
                if ( $attach_id ) {
                    update_post_meta( $post_id, '_thumbnail_id', $attach_id );
                }
                
                $term = get_term_by( 'slug', $prod['cat'], 'product_cat' );
                if ( $term ) {
                    wp_set_object_terms( $post_id, $term->term_id, 'product_cat' );
                }
            }
        } else {
            // Even if the product exists, make sure it has its featured image attached
            $thumb_id = get_post_meta( $existing->ID, '_thumbnail_id', true );
            if ( ! $thumb_id ) {
                $attach_id = anyora_sideload_theme_image( $prod['img'], $prod['title'] );
                if ( $attach_id ) {
                    update_post_meta( $existing->ID, '_thumbnail_id', $attach_id );
                }
            }
        }
    }
}
