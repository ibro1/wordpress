<?php
/**
 * Static content and dummy data generation for Wookiee Decor.
 */

defined( 'ABSPATH' ) || exit;

function wookiee_starter_pages() {
	return array(
		'home'    => array( 'title' => 'Home', 'menu' => 'Home', 'content' => '' ),
		'shop'    => array( 'title' => 'Shop', 'menu' => 'Shop', 'content' => '' ),
		'about'   => array( 'title' => 'About', 'menu' => 'About', 'content' => '<div style="background-color: var(--wookiee-bg); padding: 40px 20px;">
    <div class="wookiee-content-grid-2" style="max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: center;">
        <div>
            <div style="color: #6fbdbd; font-size: 12px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 20px; display: flex; align-items: center; gap: 15px;">
                <span style="width: 30px; height: 2px; background: #6fbdbd; display: inline-block;"></span> ABOUT OUR BUSINESS
            </div>
            <h1 style="font-size: 48px; color: var(--wookiee-navy); margin: 0 0 20px 0; font-weight: 800; letter-spacing: -2px; line-height: 1;">About Wookiee</h1>
            <p style="font-size: 18px; color: var(--wookiee-navy); font-weight: 700; margin-bottom: 20px;">
                Wookiee is a UK private-label home-storage brand and online retailer operated by Wookiee Decor Ltd.
            </p>
            <p style="font-size: 16px; color: #555; line-height: 1.7; margin-bottom: 25px;">
                We offer practical storage products for everyday areas of the home, with clear product, delivery and returns information to help customers make informed purchasing decisions.
            </p>
            <div style="display: flex; gap: 15px;">
                <a href="/shop/" class="btn">Shop our products</a>
                <a href="/contact/" class="btn-outline" style="border-radius: 30px; padding: 12px 24px; font-weight: 600; text-decoration: none; border: 2px solid var(--wookiee-navy); color: var(--wookiee-navy); display: inline-block;">Contact us</a>
            </div>
        </div>
        <div style="position: relative;">
            <img src="/wp-content/themes/wookiee-decor/assets/images/drawer-organizer.png" alt="Drawer Organizer" style="border-radius: 20px; width: 100%; max-height: 380px; object-fit: cover; box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
            <div style="position: absolute; bottom: 20px; right: 20px; background: white; padding: 25px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); max-width: 250px;">
                <div style="font-size: 10px; font-weight: 700; color: #888; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px;">UK PRIVATE-LABEL RETAILER</div>
                <div style="font-size: 24px; font-weight: 800; color: var(--wookiee-navy); margin-bottom: 5px;">Wookiee</div>
                <div style="font-size: 12px; color: #666;">Operated by Wookiee Decor Ltd</div>
            </div>
        </div>
    </div>
</div>

<div style="background: white; border-bottom: 1px solid var(--wookiee-border); border-top: 1px solid var(--wookiee-border);">
    <div class="wookiee-content-grid-4" style="max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: repeat(4, 1fr); gap: 0;">
        <div style="padding: 40px 20px; border-right: 1px solid var(--wookiee-border);">
            <div style="font-size: 10px; font-weight: 700; color: #888; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px;">LEGAL BUSINESS</div>
            <div style="font-size: 16px; font-weight: 700; color: var(--wookiee-navy); margin-bottom: 5px;">Wookiee Decor Ltd</div>
            <div style="font-size: 12px; color: #666;">Registered in Scotland</div>
        </div>
        <div style="padding: 40px 20px; border-right: 1px solid var(--wookiee-border);">
            <div style="font-size: 10px; font-weight: 700; color: #888; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px;">COMPANY NUMBER</div>
            <div style="font-size: 16px; font-weight: 700; color: var(--wookiee-navy); margin-bottom: 5px;">SC769264</div>
            <div style="font-size: 12px; color: #666;">Incorporated 2 January 2026</div>
        </div>
        <div style="padding: 40px 20px; border-right: 1px solid var(--wookiee-border);">
            <div style="font-size: 10px; font-weight: 700; color: #888; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px;">ORDER FULFILMENT</div>
            <div style="font-size: 16px; font-weight: 700; color: var(--wookiee-navy); margin-bottom: 5px;">Wookiee in Cowdenbeath</div>
            <div style="font-size: 12px; color: #666;">Stored, packed and dispatched in the UK</div>
        </div>
        <div style="padding: 40px 20px;">
            <div style="font-size: 10px; font-weight: 700; color: #888; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px;">DELIVERY MARKET</div>
            <div style="font-size: 16px; font-weight: 700; color: var(--wookiee-navy); margin-bottom: 5px;">United Kingdom</div>
            <div style="font-size: 12px; color: #666;">3-5 working days normally</div>
        </div>
    </div>
</div>

<div style="background: white; padding: 100px 20px;">
    <div class="wookiee-content-grid-2" style="max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1.2fr; gap: 80px; align-items: center;">
        <div>
            <img src="/wp-content/themes/wookiee-decor/assets/images/bathroom-shelf.png" alt="Bathroom Shelf" style="border-radius: 20px; width: 100%; box-shadow: 0 20px 40px rgba(0,0,0,0.05);">
        </div>
        <div>
            <div style="color: #6fbdbd; font-size: 12px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 20px; display: flex; align-items: center; gap: 15px;">
                <span style="width: 30px; height: 2px; background: #6fbdbd; display: inline-block;"></span> OUR RANGE AND APPROACH
            </div>
            <h2 style="font-size: 48px; color: var(--wookiee-navy); margin: 0 0 30px 0; font-weight: 800; letter-spacing: -1.5px; line-height: 1.1;">Practical storage, clearly presented.</h2>
            <p style="font-size: 18px; color: var(--wookiee-navy); font-weight: 700; margin-bottom: 20px; line-height: 1.5;">
                Our range focuses on useful storage products for kitchens, bathrooms, drawers, footwear and other everyday spaces.
            </p>
            <p style="font-size: 16px; color: #666; line-height: 1.7; margin-bottom: 20px;">
                Products sold as Wookiee-branded goods form part of our private-label range. These products may be produced for the Wookiee brand by selected third-party manufacturers.
            </p>
            <p style="font-size: 16px; color: #666; line-height: 1.7; margin-bottom: 25px;">
                Wookiee Decor Ltd operates the brand and is the retailer responsible for purchases made through this website. We manage the customer-facing product information, pricing, order administration, delivery arrangements and customer support.
            </p>
            
            <div style="background: var(--wookiee-bg); border-radius: 15px; padding: 30px; display: flex; gap: 20px; align-items: flex-start;">
                <div style="color: #dcb37b; font-weight: 700; font-size: 14px; margin-top: 3px;">01</div>
                <div>
                    <h4 style="margin: 0 0 10px 0; font-size: 18px; color: var(--wookiee-navy);">Practical selection</h4>
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
            <h1 style="font-size: 48px; color: var(--wookiee-navy); font-weight: 800; letter-spacing: -1.5px; margin: 0 0 15px 0;">Contact our team</h1>
            <p style="font-size: 16px; color: #555; margin: 0; line-height: 1.6; max-width: 600px;">
                Have a question about an order, shipping, or returns? We\'re here to help. Drop us a line below or reach out via email or phone.
            </p>
        </div>
        
        <div class="wookiee-content-grid-2" style="display: grid; grid-template-columns: 1.4fr 1fr; gap: 60px; align-items: start; box-sizing: border-box;">
            
            <!-- Contact Form Card -->
            <div style="background: white; padding: 40px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.04); border: 1px solid rgba(0,0,0,0.05); box-sizing: border-box;">
                <h2 style="font-size: 24px; color: var(--wookiee-navy); font-weight: 800; margin: 0 0 5px 0; letter-spacing: -0.5px;">Send a message</h2>
                <p style="font-size: 14px; color: #666; margin: 0 0 30px 0;">We typically reply within 24 business hours.</p>
                
                <form action="/wp-admin/admin-post.php" method="POST" style="margin: 0; display: flex; flex-direction: column; gap: 20px; box-sizing: border-box;">
                    <input type="hidden" name="action" value="wookiee_contact_form">
                    
                    <div class="wookiee-content-grid-inner-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; box-sizing: border-box;">
                        <div style="box-sizing: border-box;">
                            <label style="display: block; font-size: 11px; font-weight: 700; color: var(--wookiee-navy); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">First Name *</label>
                            <input type="text" name="first_name" required style="width: 100%; height: 48px; padding: 0 16px; border: 1px solid #e2e8f0; border-radius: 8px; font-family: inherit; font-size: 14px; box-sizing: border-box; background: #fff; color: #1a202c; outline: none; transition: border-color 0.2s;">
                        </div>
                        <div style="box-sizing: border-box;">
                            <label style="display: block; font-size: 11px; font-weight: 700; color: var(--wookiee-navy); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Last Name *</label>
                            <input type="text" name="last_name" required style="width: 100%; height: 48px; padding: 0 16px; border: 1px solid #e2e8f0; border-radius: 8px; font-family: inherit; font-size: 14px; box-sizing: border-box; background: #fff; color: #1a202c; outline: none; transition: border-color 0.2s;">
                        </div>
                    </div>
                    
                    <div style="box-sizing: border-box;">
                        <label style="display: block; font-size: 11px; font-weight: 700; color: var(--wookiee-navy); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Email Address *</label>
                        <input type="email" name="email" required style="width: 100%; height: 48px; padding: 0 16px; border: 1px solid #e2e8f0; border-radius: 8px; font-family: inherit; font-size: 14px; box-sizing: border-box; background: #fff; color: #1a202c; outline: none; transition: border-color 0.2s;">
                    </div>
                    
                    <div style="box-sizing: border-box;">
                        <label style="display: block; font-size: 11px; font-weight: 700; color: var(--wookiee-navy); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Order Number (Optional)</label>
                        <input type="text" name="order_number" style="width: 100%; height: 48px; padding: 0 16px; border: 1px solid #e2e8f0; border-radius: 8px; font-family: inherit; font-size: 14px; box-sizing: border-box; background: #fff; color: #1a202c; outline: none; transition: border-color 0.2s;">
                    </div>
                    
                    <div style="box-sizing: border-box;">
                        <label style="display: block; font-size: 11px; font-weight: 700; color: var(--wookiee-navy); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Message *</label>
                        <textarea name="message" rows="5" required style="width: 100%; padding: 16px; border: 1px solid #e2e8f0; border-radius: 8px; font-family: inherit; font-size: 14px; box-sizing: border-box; resize: vertical; background: #fff; color: #1a202c; outline: none; transition: border-color 0.2s; line-height: 1.5;"></textarea>
                    </div>
                    
                    <button type="submit" class="btn" style="width: 100%; height: 50px; display: flex; align-items: center; justify-content: center; font-size: 15px; border-radius: 8px; background: var(--wookiee-navy); color: white; font-weight: 700; cursor: pointer; border: none; transition: background-color 0.2s; box-shadow: 0 4px 12px rgba(8,29,52,0.15);">Send message</button>
                </form>
            </div>
            
            <!-- Contact Sidebar Info -->
            <div style="display: flex; flex-direction: column; gap: 30px; box-sizing: border-box;">
                
                <div style="box-sizing: border-box;">
                    <h3 style="font-size: 18px; color: var(--wookiee-navy); font-weight: 800; margin: 0 0 20px 0; letter-spacing: -0.3px;">Support channels</h3>
                    
                    <!-- Email Card -->
                    <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); border: 1px solid rgba(0,0,0,0.04); display: flex; gap: 15px; align-items: flex-start; margin-bottom: 20px; box-sizing: border-box;">
                        <span style="font-size: 20px; line-height: 1;">✉️</span>
                        <div style="box-sizing: border-box;">
                            <h4 style="margin: 0 0 5px 0; font-size: 15px; color: var(--wookiee-navy); font-weight: 700;">Email Support</h4>
                            <p style="margin: 0 0 8px 0; color: #666; font-size: 13px; line-height: 1.4;">Get in touch via email directly.</p>
                            <a href="mailto:info@wookied.com" style="color: var(--wookiee-navy); font-weight: 700; text-decoration: none; font-size: 14px; border-bottom: 1.5px solid var(--wookiee-navy); padding-bottom: 2px;">info@wookied.com</a>
                        </div>
                    </div>
                    
                    <!-- Phone Card -->
                    <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); border: 1px solid rgba(0,0,0,0.04); display: flex; gap: 15px; align-items: flex-start; box-sizing: border-box;">
                        <span style="font-size: 20px; line-height: 1;">📞</span>
                        <div style="box-sizing: border-box;">
                            <h4 style="margin: 0 0 5px 0; font-size: 15px; color: var(--wookiee-navy); font-weight: 700;">Phone Support</h4>
                            <p style="margin: 0 0 8px 0; color: #666; font-size: 13px; line-height: 1.4;">Available Monday to Friday, 9am - 5pm.</p>
                            <div style="color: var(--wookiee-navy); font-weight: 700; font-size: 14px;">+442084726126</div>
                        </div>
                    </div>
                </div>
                
                <hr style="border: 0; border-top: 1px solid rgba(0,0,0,0.08); margin: 10px 0;">
                
                <div style="box-sizing: border-box;">
                    <h3 style="font-size: 18px; color: var(--wookiee-navy); font-weight: 800; margin: 0 0 20px 0; letter-spacing: -0.3px;">Business office</h3>
                    <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); border: 1px solid rgba(0,0,0,0.04); display: flex; gap: 15px; align-items: flex-start; box-sizing: border-box;">
                        <span style="font-size: 20px; line-height: 1;">🏢</span>
                        <div style="box-sizing: border-box;">
                            <h4 style="margin: 0 0 5px 0; font-size: 15px; color: var(--wookiee-navy); font-weight: 700;">Registered Address</h4>
                            <p style="margin: 0; color: #666; font-size: 13px; line-height: 1.5;">
                                Wookiee Decor Ltd<br>
                                28 Johnston Park, Cowdenbeath, Scotland,<br>
                                KY4 9AZ, United Kingdom
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
		'mission' => array( 'title' => 'Mission', 'menu' => 'Mission', 'content' => '<!-- Hero Section --><div style="background-color: var(--wookiee-bg); padding: 40px 20px; font-family: var(--font-primary); box-sizing: border-box;"><div class="wookiee-content-grid-2" style="max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: center; box-sizing: border-box;"><div><div style="color: #6fbdbd; font-size: 12px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 20px; display: flex; align-items: center; gap: 15px;"><span style="width: 30px; height: 2px; background: #6fbdbd; display: inline-block;"></span> OUR MISSION </div><h1 style="font-size: 48px; color: var(--wookiee-navy); margin: 0 0 20px 0; font-weight: 800; letter-spacing: -2px; line-height: 1.1;">Our Mission</h1><p style="font-size: 18px; color: var(--wookiee-navy); font-weight: 700; margin-bottom: 15px; line-height: 1.5;"> To provide practical storage products selected to help make everyday spaces tidier. </p><p style="font-size: 16px; color: #555; line-height: 1.7; margin-bottom: 30px;"> We aim to reduce clutter and bring harmony to modern living spaces with simple, elegant solutions that are both highly functional and visually pleasing. </p><div style="display: flex; gap: 15px;"><a href="/shop/" class="btn">Explore our range</a></div></div><div><img src="/wp-content/themes/wookiee-decor/assets/images/wookiee-prod-shelves.png" alt="Our Mission Shelving" style="border-radius: 20px; width: 100%; max-height: 380px; object-fit: cover; box-shadow: 0 20px 40px rgba(0,0,0,0.1);"></div></div></div><!-- Core Values Section --><div style="background: white; padding: 100px 20px; font-family: var(--font-primary); box-sizing: border-box;"><div style="max-width: 1200px; margin: 0 auto; box-sizing: border-box;"><div style="text-align: center; margin-bottom: 70px; box-sizing: border-box;"><div style="color: #6fbdbd; font-size: 12px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 15px; justify-content: center; display: flex; align-items: center; gap: 10px;"> OUR VALUES </div><h2 style="font-size: 40px; color: var(--wookiee-navy); margin: 0; font-weight: 800; letter-spacing: -1px;">What drives Wookiee</h2></div><div class="wookiee-content-grid-3" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 40px; box-sizing: border-box;"><!-- Value 1 --><div style="box-sizing: border-box; text-align: center; padding: 20px;"><div style="font-size: 32px; margin-bottom: 20px;">✨</div><h3 style="font-size: 20px; color: var(--wookiee-navy); font-weight: 700; margin: 0 0 12px 0;">Simplicity</h3><p style="margin: 0; color: #666; font-size: 14px; line-height: 1.6;">De-cluttering spaces with intuitive, modular designs. We believe that storage shouldn\'t complicate your life—it should simplify it.</p></div><!-- Value 2 --><div style="box-sizing: border-box; text-align: center; padding: 20px;"><div style="font-size: 32px; margin-bottom: 20px;">🛡️</div><h3 style="font-size: 20px; color: var(--wookiee-navy); font-weight: 700; margin: 0 0 12px 0;">Quality Sourcing</h3><p style="margin: 0; color: #666; font-size: 14px; line-height: 1.6;">We select durable, premium materials—from sustainable natural bamboo to thick, food-grade, BPA-free plastics built to endure.</p></div><!-- Value 3 --><div style="box-sizing: border-box; text-align: center; padding: 20px;"><div style="font-size: 32px; margin-bottom: 20px;">🔍</div><h3 style="font-size: 20px; color: var(--wookiee-navy); font-weight: 700; margin: 0 0 12px 0;">Practical Utility</h3><p style="margin: 0; color: #666; font-size: 14px; line-height: 1.6;">Every product in our catalog is engineered to fit standard drawers, cupboards, and shelves, providing maximum volumetric efficiency.</p></div></div></div></div><!-- Sourcing Story Section --><div style="background-color: var(--wookiee-bg); padding: 100px 20px; font-family: var(--font-primary); box-sizing: border-box;"><div class="wookiee-content-grid-2" style="max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1.2fr; gap: 80px; align-items: center; box-sizing: border-box;"><div><img src="/wp-content/themes/wookiee-decor/assets/images/wookiee-prod-organizer.png" alt="Bamboo Organizer Tray" style="border-radius: 20px; width: 100%; max-height: 380px; object-fit: cover; box-shadow: 0 20px 40px rgba(0,0,0,0.05);"></div><div><div style="color: #6fbdbd; font-size: 12px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 20px; display: flex; align-items: center; gap: 15px;"><span style="width: 30px; height: 2px; background: #6fbdbd; display: inline-block;"></span> SUSTAINABILITY </div><h2 style="font-size: 48px; color: var(--wookiee-navy); margin: 0 0 30px 0; font-weight: 800; letter-spacing: -1.5px; line-height: 1.1;">Designed for longevity, sourced with care.</h2><p style="font-size: 18px; color: var(--wookiee-navy); font-weight: 700; margin-bottom: 20px; line-height: 1.5;"> We believe that home organization products shouldn\'t just be useful—they should be sustainable. </p><p style="font-size: 16px; color: #666; line-height: 1.7; margin-bottom: 20px;"> By using fast-growing FSC-certified bamboo and high-purity, recyclable polymers, we build storage solutions that stand the test of time while minimizing environmental impact. </p><p style="font-size: 16px; color: #666; line-height: 1.7; margin: 0;"> Our materials are thoroughly checked for chemical safety and toxic additives, ensuring that they are safe for kitchen food storage and kid-friendly playrooms alike. </p></div></div></div>' ),
		'activities' => array( 'title' => 'Activities', 'menu' => 'Activities', 'content' => '<div style="background-color: var(--wookiee-bg); padding: 40px 20px; font-family: var(--font-primary); box-sizing: border-box;">
    <div class="wookiee-content-grid-2" style="max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: center; box-sizing: border-box;">
        <div>
            <div style="color: #6fbdbd; font-size: 12px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 20px; display: flex; align-items: center; gap: 15px;">
                <span style="width: 30px; height: 2px; background: #6fbdbd; display: inline-block;"></span> BUSINESS OPERATIONS
            </div>
            <h1 style="font-size: 48px; color: var(--wookiee-navy); margin: 0 0 20px 0; font-weight: 800; letter-spacing: -2px; line-height: 1;">Our Activities</h1>
            <p style="font-size: 18px; color: var(--wookiee-navy); font-weight: 700; margin-bottom: 20px;">
                Retail operations, supply chain curation, and nationwide fulfillment.
            </p>
            <p style="font-size: 16px; color: #555; line-height: 1.7; margin-bottom: 0;">
                Wookiee Decor Ltd operates as a dedicated home organization and storage brand. From design selection to final doorstep delivery, our activities are geared toward making home organization simple, high-quality, and transparent.
            </p>
        </div>
        <div>
            <img src="/wp-content/themes/wookiee-decor/assets/images/logistics-package.png" alt="Logistics & Sourcing" style="border-radius: 20px; width: 100%; max-height: 380px; object-fit: cover; box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
        </div>
    </div>
</div>

<div style="background: white; padding: 100px 20px; box-sizing: border-box;">
    <div style="max-width: 1200px; margin: 0 auto; box-sizing: border-box;">
        
        <div style="text-align: center; margin-bottom: 70px; box-sizing: border-box;">
            <h2 style="font-size: 40px; color: var(--wookiee-navy); margin: 0 0 15px 0; font-weight: 800; letter-spacing: -1px;">Principal Business Activities</h2>
            <p style="font-size: 16px; color: #666; max-width: 600px; margin: 0 auto; line-height: 1.6;">How we manage product design, inventory storage, and customer logistics in the UK.</p>
        </div>

        <div class="wookiee-content-grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; box-sizing: border-box;">
            
            <!-- Card 1 -->
            <div style="background: var(--wookiee-bg); padding: 40px; border-radius: 16px; box-sizing: border-box; display: flex; gap: 20px; align-items: flex-start;">
                <div style="background: white; width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); flex-shrink: 0;">📦</div>
                <div>
                    <h3 style="font-size: 20px; color: var(--wookiee-navy); font-weight: 700; margin: 0 0 10px 0;">Private-Label Sourcing</h3>
                    <p style="margin: 0; color: #666; font-size: 14px; line-height: 1.6;">We source and curate practical, durable home storage solutions made from premium materials such as FSC-certified bamboo and durable BPA-free acrylics.</p>
                </div>
            </div>

            <!-- Card 2 -->
            <div style="background: var(--wookiee-bg); padding: 40px; border-radius: 16px; box-sizing: border-box; display: flex; gap: 20px; align-items: flex-start;">
                <div style="background: white; width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); flex-shrink: 0;">🔍</div>
                <div>
                    <h3 style="font-size: 20px; color: var(--wookiee-navy); font-weight: 700; margin: 0 0 10px 0;">Quality Assurance</h3>
                    <p style="margin: 0; color: #666; font-size: 14px; line-height: 1.6;">Every batch of products undergoes material, durability, and finishing checks to ensure they meet the high standards expected by our customers.</p>
                </div>
            </div>

            <!-- Card 3 -->
            <div style="background: var(--wookiee-bg); padding: 40px; border-radius: 16px; box-sizing: border-box; display: flex; gap: 20px; align-items: flex-start;">
                <div style="background: white; width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); flex-shrink: 0;">🏭</div>
                <div>
                    <h3 style="font-size: 20px; color: var(--wookiee-navy); font-weight: 700; margin: 0 0 10px 0;">UK Fulfillment Hub</h3>
                    <p style="margin: 0; color: #666; font-size: 14px; line-height: 1.6;">We run our packaging, storage, and order dispatch operations directly from our dedicated facility located in Cowdenbeath, United Kingdom.</p>
                </div>
            </div>

            <!-- Card 4 -->
            <div style="background: var(--wookiee-bg); padding: 40px; border-radius: 16px; box-sizing: border-box; display: flex; gap: 20px; align-items: flex-start;">
                <div style="background: white; width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); flex-shrink: 0;">🚚</div>
                <div>
                    <h3 style="font-size: 20px; color: var(--wookiee-navy); font-weight: 700; margin: 0 0 10px 0;">Logistics & Delivery</h3>
                    <p style="margin: 0; color: #666; font-size: 14px; line-height: 1.6;">Nationwide delivery is handled via reliable UK couriers, normally dispatching within 24 hours and arriving within 3-5 working days.</p>
                </div>
            </div>
            
        </div>
    </div>
</div>' ),
		'terms'   => array( 'title' => 'Terms and conditions', 'menu' => '', 'content' => '
<div style="max-width:860px; margin:0 auto; padding:40px 20px; font-family:inherit; color:#333; line-height:1.7;">

<h1 style="font-size:32px; font-weight:800; color:#081d34; margin-bottom:6px;">Terms and Conditions</h1>
<p style="color:#888; font-size:14px; margin-bottom:40px;">Last updated: July 2025 &nbsp;|&nbsp; Wookiee Decor Ltd &nbsp;|&nbsp; Company No. SC769264</p>

<p>These terms and conditions govern your use of the Wookiee website at <strong>wookied.com</strong> and any purchase you make from us. Please read them carefully before placing an order. By purchasing from us, you agree to be bound by these terms.</p>

<p>These terms do not affect your statutory rights under UK consumer law, including the Consumer Rights Act 2015 and the Consumer Contracts (Information, Cancellation and Additional Charges) Regulations 2013.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">1. About Us</h2>
<p>Wookiee is a trading name of <strong>Wookiee Decor Ltd</strong>, a private limited company registered in England and Wales.</p>
<ul style="padding-left:22px;">
  <li><strong>Company number:</strong> SC769264</li>
  <li><strong>Registered address:</strong> 28 Johnston Park, Cowdenbeath, Scotland, KY4 9AZ</li>
  <li><strong>Email:</strong> <a href="mailto:info@wookied.com" style="color:#6fbdbd;">info@wookied.com</a></li>
  <li><strong>Phone:</strong> +442084726126</li>
</ul>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">2. Placing an Order</h2>
<p>When you place an order on our website, you are making an offer to purchase goods. Your order constitutes an offer to Wookiee to buy the product(s) listed. All orders are subject to availability and acceptance by us.</p>
<p>You will receive an order confirmation email once your order is placed. This email is an acknowledgement that we have received your order and does not constitute acceptance of it. Acceptance occurs when we dispatch your goods and send a dispatch confirmation email.</p>
<p>We reserve the right to refuse or cancel any order at our discretion, including if a product is found to be incorrectly priced. If we cancel an order, we will issue a full refund to your original payment method.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">3. Prices and Payment</h2>
<p>All prices displayed on our website are in pounds sterling (GBP) and include VAT where applicable. We reserve the right to change prices at any time. The price you pay is the price displayed at the time you place your order.</p>
<p>We accept payment by major debit and credit cards, PayPal, Apple Pay, and Google Pay. Full payment is required at checkout before your order is processed. All payments are processed securely via an encrypted payment gateway. We do not store your full card details.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">4. Delivery</h2>
<p>We deliver to addresses within the United Kingdom. Estimated delivery times and any applicable delivery charges will be shown at checkout before you complete your purchase.</p>
<p>Our standard estimated delivery time is <strong>3–5 business days</strong> from dispatch. We aim to dispatch orders within 1–2 business days of payment confirmation. Delivery timescales are estimates only and not guaranteed.</p>
<p>Risk in the goods passes to you once they are delivered to your specified delivery address. Title to the goods passes to you upon full payment.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">5. Your Right to Cancel (Cooling-Off Period)</h2>
<p>Under the Consumer Contracts (Information, Cancellation and Additional Charges) Regulations 2013, you have the right to cancel your order within <strong>14 days</strong> of receiving your goods, without giving any reason.</p>
<p>To exercise this right, you must notify us clearly within the 14-day period by emailing <a href="mailto:info@wookied.com" style="color:#6fbdbd;">info@wookied.com</a>. You must then return the goods to us within a further 14 days of notifying us.</p>
<p>We will issue a full refund, including the original standard delivery charge, within <strong>14 days</strong> of receiving the returned goods or evidence of return, whichever is earlier. We may reduce the refund to reflect any diminishment in the value of the goods caused by handling beyond what is necessary to inspect them.</p>
<p>Return shipping costs for change-of-mind cancellations are your responsibility unless otherwise stated.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">6. Faulty or Incorrect Goods</h2>
<p>Under the Consumer Rights Act 2015, you have the right to goods that are of satisfactory quality, fit for purpose, and as described. If your goods are faulty, damaged, or not as described:</p>
<ul style="padding-left:22px;">
  <li><strong>Within 30 days of delivery:</strong> You may reject the goods and request a full refund, repair, or replacement.</li>
  <li><strong>After 30 days (up to 6 months):</strong> We will offer a repair or replacement. If neither is possible or both fail, you are entitled to a partial or full refund.</li>
  <li><strong>After 6 months:</strong> You must demonstrate the fault existed at the time of delivery. If proven, we will offer a repair, replacement, or partial refund.</li>
</ul>
<p>To report a fault, please contact us at <a href="mailto:info@wookied.com" style="color:#6fbdbd;">info@wookied.com</a> with your order number and a description or photo of the issue.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">7. Commercial Returns Policy</h2>
<p>In addition to your statutory rights, we offer a <strong>30-day commercial returns policy</strong>. If you change your mind about a purchase within 30 days of receipt, you may return the goods for a refund provided they are unused, in their original packaging, and in resalable condition. Please see our full <a href="/returns/" style="color:#6fbdbd;">Returns, Refunds and Cancellations Policy</a> for details.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">8. Limitation of Liability</h2>
<p>Nothing in these terms limits or excludes our liability for death or personal injury caused by our negligence, fraud or fraudulent misrepresentation, or any liability that cannot be excluded under applicable UK law.</p>
<p>Subject to the above, our total liability to you in connection with any order shall not exceed the price paid by you for the goods in that order. We are not liable for any indirect or consequential losses, including loss of profit, loss of data, or loss of opportunity, except where required by law.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">9. Intellectual Property</h2>
<p>All content on this website, including text, images, graphics, logos, and design, is the property of Wookiee Decor Ltd or its licensors and is protected by copyright and other intellectual property laws. You may not reproduce, redistribute, or republish any part of this website without our express written permission.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">10. Privacy</h2>
<p>We process your personal data in accordance with our <a href="/privacy/" style="color:#6fbdbd;">Privacy Policy</a>, which forms part of these terms.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">11. Governing Law</h2>
<p>These terms are governed by the laws of England and Wales. Any disputes shall be subject to the exclusive jurisdiction of the courts of England and Wales, without prejudice to your statutory consumer rights.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">12. Changes to These Terms</h2>
<p>We may update these terms from time to time. The version displayed on our website at the time you place your order is the version that applies to your purchase.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<p style="font-size:13px; color:#999;">If you have any questions about these terms, please contact us at <a href="mailto:info@wookied.com" style="color:#6fbdbd;">info@wookied.com</a>.</p>

</div>
' ),
		'shipping'=> array( 'title' => 'Shipping policy', 'menu' => '', 'content' => '
<div style="max-width:860px; margin:0 auto; padding:40px 20px; font-family:inherit; color:#333; line-height:1.7;">

<h1 style="font-size:32px; font-weight:800; color:#081d34; margin-bottom:6px;">Shipping Policy</h1>
<p style="color:#888; font-size:14px; margin-bottom:40px;">Last updated: July 2025 &nbsp;|&nbsp; Wookiee Decor Ltd &nbsp;|&nbsp; Company No. SC769264</p>

<p>We want your order to arrive quickly and safely. Below you will find everything you need to know about how we ship, what it costs, and what to do if something goes wrong.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">Where We Deliver</h2>
<p>We currently deliver to addresses within the <strong>United Kingdom</strong> only. We do not ship internationally at this time.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">Delivery Options and Costs</h2>
<table style="width:100%; border-collapse:collapse; font-size:15px; margin-bottom:20px;">
  <thead>
    <tr style="background:#f4f5f0;">
      <th style="text-align:left; padding:12px 16px; color:#081d34;">Service</th>
      <th style="text-align:left; padding:12px 16px; color:#081d34;">Estimated Delivery</th>
      <th style="text-align:left; padding:12px 16px; color:#081d34;">Cost</th>
    </tr>
  </thead>
  <tbody>
    <tr style="border-bottom:1px solid #eee;">
      <td style="padding:12px 16px;">Standard Delivery</td>
      <td style="padding:12px 16px;">3–5 business days</td>
      <td style="padding:12px 16px;">Displayed at checkout</td>
    </tr>
    <tr>
      <td style="padding:12px 16px;">Free Delivery</td>
      <td style="padding:12px 16px;">3–5 business days</td>
      <td style="padding:12px 16px;">Free on qualifying orders (threshold shown at checkout)</td>
    </tr>
  </tbody>
</table>
<p style="font-size:13px; color:#888;">Delivery charges are calculated at checkout based on your order and location. The total will always be shown before you complete your purchase.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">Order Processing</h2>
<p>Orders placed before <strong>2:00 pm on business days (Monday to Friday, excluding UK public holidays)</strong> are typically dispatched the same day or the next business day. Orders placed after this cut-off, or on weekends and public holidays, will be processed on the next available business day.</p>
<p>You will receive a dispatch confirmation email with tracking information once your order has been collected by our courier.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">Delivery Timescales</h2>
<p>Our estimated delivery timescale is <strong>3–5 business days</strong> from the date of dispatch. This is an estimate, not a guarantee. Delays can occasionally occur due to high courier demand, adverse weather, or circumstances outside our control. If your order has not arrived within 7 business days of dispatch, please contact us.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">Tracking Your Order</h2>
<p>Once your order is dispatched, you will receive an email containing your tracking number and a link to track your parcel. You can also log in to your account at <a href="/my-account/" style="color:#6fbdbd;">wookied.com/my-account</a> to view your order status.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">Failed Deliveries</h2>
<p>If no one is available at the delivery address, our courier will typically leave a card with instructions for redelivery or collection from a local depot. If a parcel is returned to us as undeliverable after failed delivery attempts, we will contact you to arrange redelivery. A redelivery charge may apply.</p>
<p>Please ensure your delivery address is accurate at checkout. We cannot be held responsible for parcels lost or delayed due to an incorrect address being provided.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">Damaged or Lost Parcels</h2>
<p>If your order arrives damaged, please take photos immediately and contact us at <a href="mailto:info@wookied.com" style="color:#6fbdbd;">info@wookied.com</a> within 48 hours of delivery. If your tracked parcel shows as delivered but you have not received it, please check with neighbours and your local courier depot first, then contact us and we will investigate.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">Contact Us</h2>
<p>If you have any questions about your delivery, please reach out to our support team:</p>
<ul style="padding-left:22px;">
  <li>Email: <a href="mailto:info@wookied.com" style="color:#6fbdbd;">info@wookied.com</a></li>
  <li>Phone: +442084726126</li>
  <li>Address: Wookiee Decor Ltd, 28 Johnston Park, Cowdenbeath, Scotland, KY4 9AZ</li>
</ul>

</div>
' ),
		'returns' => array( 'title' => 'Returns, refunds and cancellations', 'menu' => '', 'content' => '
<div style="max-width:860px; margin:0 auto; padding:40px 20px; font-family:inherit; color:#333; line-height:1.7;">

<h1 style="font-size:32px; font-weight:800; color:#081d34; margin-bottom:6px;">Returns, Refunds and Cancellations</h1>
<p style="color:#888; font-size:14px; margin-bottom:40px;">Last updated: July 2025 &nbsp;|&nbsp; Wookiee Decor Ltd &nbsp;|&nbsp; Company No. SC769264</p>

<p>We want you to be completely satisfied with your purchase. If something is not right, we will do everything we reasonably can to resolve it quickly and fairly.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">Your Statutory Right to Cancel (14-Day Cooling-Off Period)</h2>
<p>Under the Consumer Contracts (Information, Cancellation and Additional Charges) Regulations 2013, you have the right to cancel your order within <strong>14 days</strong> of the day you (or someone you nominate) receive the goods, without giving any reason.</p>
<p>To exercise this right, you must inform us clearly within the 14-day period. The easiest way is to email us at <a href="mailto:info@wookied.com" style="color:#6fbdbd;">info@wookied.com</a> with your name, order number, and a clear statement that you wish to cancel.</p>
<p>Once you have notified us, you have a further <strong>14 days</strong> to return the goods to us. You are responsible for the cost of return shipping for change-of-mind cancellations.</p>
<p>We will issue a full refund — including the original standard delivery charge — within <strong>14 days</strong> of receiving the returned goods or evidence of return, whichever is earlier. We process refunds to your original payment method. We may reduce the refund amount to reflect any reduction in the value of the goods if you have handled them beyond what is necessary to check their nature and condition.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">Our 30-Day Commercial Returns Policy</h2>
<p>In addition to your statutory rights, we voluntarily extend our returns window to <strong>30 days</strong> from the date of delivery for change-of-mind returns. To qualify:</p>
<ul style="padding-left:22px;">
  <li>The item must be unused and in its original condition.</li>
  <li>It must be returned in its original packaging.</li>
  <li>Proof of purchase (your order confirmation email) is required.</li>
</ul>
<p>Returns that fall outside the statutory 14-day cooling-off period (days 15–30) will be refunded as store credit or exchange, at our discretion, unless the goods are faulty.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">Faulty, Damaged or Incorrect Items</h2>
<p>Under the Consumer Rights Act 2015, you are entitled to goods that are of satisfactory quality, fit for purpose, and as described. If your item arrives faulty, damaged, or different from what you ordered, your rights are as follows:</p>
<ul style="padding-left:22px;">
  <li><strong>Within 30 days of delivery:</strong> You may reject the goods and request a full refund, or we will offer a repair or replacement.</li>
  <li><strong>Between 30 days and 6 months:</strong> We will repair or replace the item. If that is not possible or fails, you are entitled to a refund.</li>
  <li><strong>After 6 months:</strong> You must provide evidence that the fault existed at the time of purchase. If accepted, we will offer a repair, replacement, or partial refund.</li>
</ul>
<p>We cover return postage costs for items that are faulty, damaged, or incorrectly sent.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">How to Start a Return</h2>
<ol style="padding-left:22px;">
  <li>Email <a href="mailto:info@wookied.com" style="color:#6fbdbd;">info@wookied.com</a> with your order number, reason for return, and photos if the item is damaged or faulty.</li>
  <li>Our team will respond within 2 business days with a returns authorisation and instructions.</li>
  <li>Pack the item securely and send it to the address provided in our response.</li>
  <li>Once we receive and inspect the return, we will process your refund within 5–14 business days.</li>
</ol>
<p>Please do not send items back without contacting us first, as this may delay your refund.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">Cancellations</h2>
<p>If you wish to cancel an order before it has been dispatched, please contact us immediately at <a href="mailto:info@wookied.com" style="color:#6fbdbd;">info@wookied.com</a>. We will do our best to stop the order before it ships. If the order has already been dispatched, you will need to follow the returns process above once the parcel arrives.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">Refunds</h2>
<p>Approved refunds are processed to your original payment method. Card refunds typically take 3–5 business days to appear in your account once processed, depending on your card issuer. PayPal refunds are usually received within 24 hours.</p>
<p>We do not charge a restocking fee for returned items that are in their original condition.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">Non-Returnable Items</h2>
<p>We are unable to accept returns on items that have been personalised or custom-made to your specifications (if applicable). Items that have been used, damaged by the customer, or returned without original packaging may not be eligible for a full refund.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<p style="font-size:13px; color:#999;">Nothing in this policy affects your statutory rights under UK consumer law. For queries, contact <a href="mailto:info@wookied.com" style="color:#6fbdbd;">info@wookied.com</a> or call +442084726126.</p>

</div>
' ),
		'payment' => array( 'title' => 'Payment policy', 'menu' => '', 'content' => '
<div style="max-width:860px; margin:0 auto; padding:40px 20px; font-family:inherit; color:#333; line-height:1.7;">

<h1 style="font-size:32px; font-weight:800; color:#081d34; margin-bottom:6px;">Payment Policy</h1>
<p style="color:#888; font-size:14px; margin-bottom:40px;">Last updated: July 2025 &nbsp;|&nbsp; Wookiee Decor Ltd &nbsp;|&nbsp; Company No. SC769264</p>

<p>This page explains how we handle payments, what methods we accept, and how we keep your financial information safe.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">Accepted Payment Methods</h2>
<p>We accept the following payment methods at checkout:</p>
<ul style="padding-left:22px;">
  <li>Visa (debit and credit)</li>
  <li>Mastercard (debit and credit)</li>
  <li>American Express</li>
  <li>PayPal</li>
  <li>Apple Pay</li>
  <li>Google Pay</li>
</ul>
<p>All prices are displayed in <strong>British pounds sterling (GBP)</strong> and include VAT where applicable. The total amount you will be charged — including any applicable delivery costs — is shown clearly at checkout before you confirm your order.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">When You Are Charged</h2>
<p>Payment is taken at the time you place your order. Your card or payment method is charged immediately upon order confirmation. We do not operate a pay-later or instalment service.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">Payment Security</h2>
<p>All transactions on wookied.com are processed through an industry-standard secure payment gateway using SSL (Secure Socket Layer) encryption. Your card details are transmitted directly to our payment processor and are never stored on our servers. We do not have access to your full card number, CVV, or banking credentials.</p>
<p>Our payment infrastructure is compliant with industry security standards to protect against unauthorised access and fraud.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">Currency</h2>
<p>All transactions are processed in GBP (British pounds sterling). If your bank account is held in a different currency, your card issuer or PayPal will apply their standard exchange rate and may charge a foreign transaction fee. We are not responsible for any such fees or exchange rate differences.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">Failed Payments</h2>
<p>If your payment is declined, your order will not be confirmed and no charge will be made. Please check your card details and billing address, or try an alternative payment method. If you continue to experience issues, please contact your bank or payment provider first, then reach out to us at <a href="mailto:info@wookied.com" style="color:#6fbdbd;">info@wookied.com</a>.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">Refunds</h2>
<p>Approved refunds are returned to the original payment method used at checkout. We do not issue refunds via a different method. Please see our <a href="/returns/" style="color:#6fbdbd;">Returns, Refunds and Cancellations Policy</a> for full details on how and when refunds are processed.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">Fraud Prevention</h2>
<p>To protect our customers and our business, we may carry out security checks on orders. In some cases, we may contact you to verify your identity or the details of your order before processing it. We reserve the right to cancel orders where we reasonably suspect fraudulent activity.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<p style="font-size:13px; color:#999;">Questions about a payment? Contact us at <a href="mailto:info@wookied.com" style="color:#6fbdbd;">info@wookied.com</a> or call +442084726126.</p>

</div>
' ),
		'privacy' => array( 'title' => 'Privacy policy', 'menu' => '', 'content' => '
<div style="max-width:860px; margin:0 auto; padding:40px 20px; font-family:inherit; color:#333; line-height:1.7;">

<h1 style="font-size:32px; font-weight:800; color:#081d34; margin-bottom:6px;">Privacy Policy</h1>
<p style="color:#888; font-size:14px; margin-bottom:40px;">Last updated: July 2025 &nbsp;|&nbsp; Wookiee Decor Ltd &nbsp;|&nbsp; Company No. SC769264</p>

<p>This privacy policy explains how Wookiee Decor Ltd collects, uses, stores, and protects your personal information when you visit wookied.com or purchase from us. It also explains your rights under UK data protection law.</p>
<p>We are committed to handling your data responsibly and transparently. We only collect what we need, we keep it secure, and we never sell it.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">1. Who We Are (Data Controller)</h2>
<p>The data controller for your personal information is:</p>
<ul style="padding-left:22px;">
  <li><strong>Wookiee Decor Ltd</strong></li>
  <li>28 Johnston Park, Cowdenbeath, Scotland, KY4 9AZ</li>
  <li>Company number: SC769264</li>
  <li>Email: <a href="mailto:info@wookied.com" style="color:#6fbdbd;">info@wookied.com</a></li>
</ul>
<p>We process your data in accordance with the UK General Data Protection Regulation (UK GDPR) and the Data Protection Act 2018.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">2. What Information We Collect</h2>
<p>We collect and use the following types of personal information:</p>
<ul style="padding-left:22px;">
  <li><strong>Identity and contact information:</strong> name, email address, billing address, delivery address, phone number.</li>
  <li><strong>Order and transaction information:</strong> items purchased, order value, payment status, and order history.</li>
  <li><strong>Account information:</strong> username, encrypted password, and account preferences (if you create an account).</li>
  <li><strong>Communication records:</strong> emails, messages, or other correspondence you send us.</li>
  <li><strong>Technical and usage data:</strong> IP address, browser type, pages visited, time on site, referring URLs. This is collected automatically via cookies and analytics tools.</li>
</ul>
<p>We do not collect or store sensitive personal data (such as health information or financial data beyond what is processed via our secure payment gateway).</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">3. How We Use Your Information</h2>
<table style="width:100%; border-collapse:collapse; font-size:14px; margin-bottom:20px;">
  <thead>
    <tr style="background:#f4f5f0;">
      <th style="text-align:left; padding:12px 16px; color:#081d34;">Purpose</th>
      <th style="text-align:left; padding:12px 16px; color:#081d34;">Lawful Basis (UK GDPR Art.6)</th>
    </tr>
  </thead>
  <tbody>
    <tr style="border-bottom:1px solid #eee;">
      <td style="padding:12px 16px;">Processing and fulfilling your order</td>
      <td style="padding:12px 16px;">Contract performance (Art.6(1)(b))</td>
    </tr>
    <tr style="border-bottom:1px solid #eee;">
      <td style="padding:12px 16px;">Sending order confirmation and dispatch emails</td>
      <td style="padding:12px 16px;">Contract performance (Art.6(1)(b))</td>
    </tr>
    <tr style="border-bottom:1px solid #eee;">
      <td style="padding:12px 16px;">Responding to your enquiries and customer support</td>
      <td style="padding:12px 16px;">Contract performance / Legitimate interests (Art.6(1)(b)(f))</td>
    </tr>
    <tr style="border-bottom:1px solid #eee;">
      <td style="padding:12px 16px;">Processing returns and refunds</td>
      <td style="padding:12px 16px;">Contract performance / Legal obligation (Art.6(1)(b)(c))</td>
    </tr>
    <tr style="border-bottom:1px solid #eee;">
      <td style="padding:12px 16px;">Maintaining financial and tax records</td>
      <td style="padding:12px 16px;">Legal obligation (Art.6(1)(c))</td>
    </tr>
    <tr style="border-bottom:1px solid #eee;">
      <td style="padding:12px 16px;">Improving our website and customer experience</td>
      <td style="padding:12px 16px;">Legitimate interests (Art.6(1)(f))</td>
    </tr>
    <tr style="border-bottom:1px solid #eee;">
      <td style="padding:12px 16px;">Sending marketing emails (if opted in)</td>
      <td style="padding:12px 16px;">Consent (Art.6(1)(a))</td>
    </tr>
    <tr>
      <td style="padding:12px 16px;">Detecting and preventing fraud</td>
      <td style="padding:12px 16px;">Legitimate interests / Legal obligation (Art.6(1)(c)(f))</td>
    </tr>
  </tbody>
</table>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">4. Who We Share Your Information With</h2>
<p>We do not sell your personal data. We share it only where necessary to provide our services:</p>
<ul style="padding-left:22px;">
  <li><strong>Payment processors:</strong> Your payment information is processed securely by our payment gateway provider. We do not store your card details.</li>
  <li><strong>Delivery couriers:</strong> We share your name and delivery address with our courier to fulfil your order.</li>
  <li><strong>Analytics providers:</strong> We use analytics tools (such as Google Analytics) to understand how visitors use our website. This data is aggregated and anonymised where possible.</li>
  <li><strong>Email service providers:</strong> We use third-party providers to send order and marketing emails on our behalf.</li>
  <li><strong>Legal and regulatory authorities:</strong> We may disclose your information if required by law, court order, or to protect our rights.</li>
</ul>
<p>All third parties we work with are required to handle your data securely and in accordance with applicable data protection law.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">5. Data Retention</h2>
<p>We retain your personal information only for as long as necessary for the purposes for which it was collected:</p>
<ul style="padding-left:22px;">
  <li><strong>Order and transaction records:</strong> 7 years (as required by HMRC for tax purposes).</li>
  <li><strong>Account information:</strong> Until you request deletion of your account.</li>
  <li><strong>Marketing preferences:</strong> Until you unsubscribe or withdraw consent.</li>
  <li><strong>Customer service communications:</strong> Up to 3 years from the date of last contact.</li>
  <li><strong>Cookie and analytics data:</strong> Typically up to 12 months (see our Cookie Policy for details).</li>
</ul>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">6. Cookies</h2>
<p>We use cookies and similar tracking technologies on our website. For full details of the cookies we use and how to manage your preferences, please see our <a href="/cookie/" style="color:#6fbdbd;">Cookie Policy</a>.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">7. Your Rights</h2>
<p>Under the UK GDPR, you have the following rights regarding your personal data:</p>
<ul style="padding-left:22px;">
  <li><strong>Right of access:</strong> Request a copy of the personal data we hold about you.</li>
  <li><strong>Right to rectification:</strong> Ask us to correct inaccurate or incomplete data.</li>
  <li><strong>Right to erasure:</strong> Ask us to delete your personal data where we no longer have a lawful basis to hold it.</li>
  <li><strong>Right to restrict processing:</strong> Ask us to pause the use of your data in certain circumstances.</li>
  <li><strong>Right to data portability:</strong> Receive your data in a structured, machine-readable format.</li>
  <li><strong>Right to object:</strong> Object to us processing your data based on legitimate interests or for direct marketing purposes.</li>
  <li><strong>Rights related to automated decision-making:</strong> We do not make automated decisions that have legal or significant effects on you.</li>
</ul>
<p>To exercise any of these rights, please email us at <a href="mailto:info@wookied.com" style="color:#6fbdbd;">info@wookied.com</a>. We will respond within one calendar month. We do not charge a fee for exercising your rights unless a request is manifestly unfounded or excessive.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">8. How We Keep Your Data Secure</h2>
<p>We take the security of your personal data seriously. We use SSL encryption across our website, restrict internal access to your data to authorised personnel only, and use reputable, security-conscious third-party services. While we take all reasonable steps to protect your data, no method of transmission over the internet is completely secure.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">9. International Transfers</h2>
<p>We primarily store and process your data within the United Kingdom. Some of our third-party service providers (such as analytics tools) may transfer data internationally. Where this occurs, we ensure appropriate safeguards are in place in accordance with UK GDPR requirements.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">10. Marketing Communications</h2>
<p>We will only send you marketing emails if you have opted in to receive them. You can unsubscribe at any time by clicking the unsubscribe link in any marketing email or by emailing us at <a href="mailto:info@wookied.com" style="color:#6fbdbd;">info@wookied.com</a>. Opting out of marketing does not affect your order-related communications.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">11. Complaints</h2>
<p>If you are unhappy with how we have handled your personal data, please contact us first at <a href="mailto:info@wookied.com" style="color:#6fbdbd;">info@wookied.com</a> so we can try to resolve your concern.</p>
<p>You also have the right to lodge a complaint with the UK Information Commissioner\'s Office (ICO):</p>
<ul style="padding-left:22px;">
  <li>Website: <a href="https://ico.org.uk/make-a-complaint" style="color:#6fbdbd;" target="_blank" rel="noopener">ico.org.uk/make-a-complaint</a></li>
  <li>Helpline: 0303 123 1113</li>
</ul>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">12. Changes to This Policy</h2>
<p>We may update this privacy policy from time to time. When we make significant changes, we will update the "Last updated" date at the top of this page. We encourage you to review this policy periodically.</p>

</div>
' ),
		'cookie'  => array( 'title' => 'Cookie policy', 'menu' => '', 'content' => '
<div style="max-width:860px; margin:0 auto; padding:40px 20px; font-family:inherit; color:#333; line-height:1.7;">

<h1 style="font-size:32px; font-weight:800; color:#081d34; margin-bottom:6px;">Cookie Policy</h1>
<p style="color:#888; font-size:14px; margin-bottom:40px;">Last updated: July 2025 &nbsp;|&nbsp; Wookiee Decor Ltd &nbsp;|&nbsp; Company No. SC769264</p>

<p>This cookie policy explains what cookies are, which cookies we use on wookied.com, why we use them, and how you can manage your preferences. It should be read alongside our <a href="/privacy/" style="color:#6fbdbd;">Privacy Policy</a>.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">What Are Cookies?</h2>
<p>Cookies are small text files placed on your device when you visit a website. They are widely used to make websites function properly, to remember your preferences, and to provide website owners with information about how visitors use their site.</p>
<p>Cookies are governed in the UK by the Privacy and Electronic Communications Regulations (PECR) alongside the UK GDPR.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">Cookies We Use</h2>

<h3 style="font-size:17px; font-weight:700; color:#081d34; margin-bottom:8px;">1. Strictly Necessary Cookies</h3>
<p>These cookies are essential for the website to function. They enable core features such as your shopping cart, account login, and secure checkout. Without these cookies, services you have requested (such as completing a purchase) cannot be provided. These cookies do not require your consent.</p>
<table style="width:100%; border-collapse:collapse; font-size:14px; margin-bottom:24px;">
  <thead>
    <tr style="background:#f4f5f0;">
      <th style="text-align:left; padding:10px 14px; color:#081d34;">Cookie name</th>
      <th style="text-align:left; padding:10px 14px; color:#081d34;">Purpose</th>
      <th style="text-align:left; padding:10px 14px; color:#081d34;">Duration</th>
    </tr>
  </thead>
  <tbody>
    <tr style="border-bottom:1px solid #eee;">
      <td style="padding:10px 14px;">woocommerce_cart_hash</td>
      <td style="padding:10px 14px;">Tracks your shopping cart contents</td>
      <td style="padding:10px 14px;">Session</td>
    </tr>
    <tr style="border-bottom:1px solid #eee;">
      <td style="padding:10px 14px;">woocommerce_session</td>
      <td style="padding:10px 14px;">Maintains your active shopping session</td>
      <td style="padding:10px 14px;">2 days</td>
    </tr>
    <tr style="border-bottom:1px solid #eee;">
      <td style="padding:10px 14px;">wp-settings</td>
      <td style="padding:10px 14px;">Stores your site preferences</td>
      <td style="padding:10px 14px;">1 year</td>
    </tr>
    <tr>
      <td style="padding:10px 14px;">PHPSESSID</td>
      <td style="padding:10px 14px;">General session management</td>
      <td style="padding:10px 14px;">Session</td>
    </tr>
  </tbody>
</table>

<h3 style="font-size:17px; font-weight:700; color:#081d34; margin-bottom:8px;">2. Analytics Cookies</h3>
<p>We use analytics cookies to understand how visitors interact with our website, which pages are most popular, and where visitors come from. This helps us improve our website and user experience. These cookies collect data in an aggregated, anonymous form and require your consent.</p>
<table style="width:100%; border-collapse:collapse; font-size:14px; margin-bottom:24px;">
  <thead>
    <tr style="background:#f4f5f0;">
      <th style="text-align:left; padding:10px 14px; color:#081d34;">Cookie name</th>
      <th style="text-align:left; padding:10px 14px; color:#081d34;">Provider</th>
      <th style="text-align:left; padding:10px 14px; color:#081d34;">Purpose</th>
      <th style="text-align:left; padding:10px 14px; color:#081d34;">Duration</th>
    </tr>
  </thead>
  <tbody>
    <tr style="border-bottom:1px solid #eee;">
      <td style="padding:10px 14px;">_ga</td>
      <td style="padding:10px 14px;">Google Analytics</td>
      <td style="padding:10px 14px;">Distinguishes unique users</td>
      <td style="padding:10px 14px;">2 years</td>
    </tr>
    <tr style="border-bottom:1px solid #eee;">
      <td style="padding:10px 14px;">_ga_*</td>
      <td style="padding:10px 14px;">Google Analytics</td>
      <td style="padding:10px 14px;">Maintains session state</td>
      <td style="padding:10px 14px;">2 years</td>
    </tr>
    <tr>
      <td style="padding:10px 14px;">_gid</td>
      <td style="padding:10px 14px;">Google Analytics</td>
      <td style="padding:10px 14px;">Distinguishes users for 24-hour sessions</td>
      <td style="padding:10px 14px;">24 hours</td>
    </tr>
  </tbody>
</table>

<h3 style="font-size:17px; font-weight:700; color:#081d34; margin-bottom:8px;">3. Marketing and Advertising Cookies</h3>
<p>Marketing cookies are used to display relevant advertisements to you on other websites and to measure the effectiveness of our advertising campaigns. They are set by third-party advertising networks and require your consent.</p>
<table style="width:100%; border-collapse:collapse; font-size:14px; margin-bottom:24px;">
  <thead>
    <tr style="background:#f4f5f0;">
      <th style="text-align:left; padding:10px 14px; color:#081d34;">Cookie name</th>
      <th style="text-align:left; padding:10px 14px; color:#081d34;">Provider</th>
      <th style="text-align:left; padding:10px 14px; color:#081d34;">Purpose</th>
      <th style="text-align:left; padding:10px 14px; color:#081d34;">Duration</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td style="padding:10px 14px;">_gcl_au</td>
      <td style="padding:10px 14px;">Google Ads</td>
      <td style="padding:10px 14px;">Conversion tracking</td>
      <td style="padding:10px 14px;">3 months</td>
    </tr>
  </tbody>
</table>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">Your Consent</h2>
<p>When you first visit wookied.com, you will be presented with a cookie consent notice asking for your permission to set non-essential cookies. You can choose to accept all cookies, accept only essential cookies, or customise your preferences.</p>
<p>You can change or withdraw your consent at any time by visiting our <a href="/cookie-pref/" style="color:#6fbdbd;">Cookie Preferences</a> page.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">Managing Cookies in Your Browser</h2>
<p>You can also control cookies through your browser settings. Most browsers allow you to refuse or delete cookies. Please note that disabling cookies may affect the functionality of our website, including the shopping cart and checkout process. Guidance on managing cookies in popular browsers:</p>
<ul style="padding-left:22px;">
  <li><a href="https://support.google.com/chrome/answer/95647" style="color:#6fbdbd;" target="_blank" rel="noopener">Google Chrome</a></li>
  <li><a href="https://support.mozilla.org/en-US/kb/cookies-information-websites-store-on-your-computer" style="color:#6fbdbd;" target="_blank" rel="noopener">Mozilla Firefox</a></li>
  <li><a href="https://support.apple.com/en-gb/guide/safari/sfri11471/mac" style="color:#6fbdbd;" target="_blank" rel="noopener">Apple Safari</a></li>
  <li><a href="https://support.microsoft.com/en-gb/topic/delete-and-manage-cookies-168dab11-0753-043d-7c16-ede5947fc64d" style="color:#6fbdbd;" target="_blank" rel="noopener">Microsoft Edge</a></li>
</ul>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">Third-Party Opt-Outs</h2>
<ul style="padding-left:22px;">
  <li>Google Analytics: <a href="https://tools.google.com/dlpage/gaoptout" style="color:#6fbdbd;" target="_blank" rel="noopener">tools.google.com/dlpage/gaoptout</a></li>
  <li>Google Ads: <a href="https://adssettings.google.com" style="color:#6fbdbd;" target="_blank" rel="noopener">adssettings.google.com</a></li>
  <li>General advertising opt-out: <a href="https://www.youronlinechoices.com" style="color:#6fbdbd;" target="_blank" rel="noopener">youronlinechoices.com</a></li>
</ul>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">Changes to This Policy</h2>
<p>We may update this cookie policy from time to time. When we do, we will update the date at the top of this page. If the changes are significant, we will present you with a new cookie consent notice.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<p style="font-size:13px; color:#999;">Questions about cookies? Email us at <a href="mailto:info@wookied.com" style="color:#6fbdbd;">info@wookied.com</a>.</p>

</div>
' ),
		'cookie-pref' => array( 'title' => 'Cookie preferences', 'menu' => '', 'content' => '
<div style="max-width:860px; margin:0 auto; padding:40px 20px; font-family:inherit; color:#333; line-height:1.7;">

<h1 style="font-size:32px; font-weight:800; color:#081d34; margin-bottom:6px;">Cookie Preferences</h1>
<p style="color:#888; font-size:14px; margin-bottom:40px;">Wookiee Decor Ltd &nbsp;|&nbsp; wookied.com</p>

<p>This page explains how to manage the cookies set by wookied.com. You are always in control of the cookies placed on your device. Below is a summary of each cookie category and how to manage your choices.</p>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<div style="background:#f4f5f0; border-radius:12px; padding:28px 32px; margin-bottom:24px;">
  <h2 style="font-size:18px; font-weight:700; color:#081d34; margin:0 0 8px 0;">✅ Strictly Necessary Cookies</h2>
  <p style="margin:0 0 12px 0; font-size:14px;">These cookies are essential for the website to work. They keep your shopping cart intact, allow you to log in, and secure your checkout. They cannot be turned off as the site cannot function without them.</p>
  <p style="margin:0; font-size:13px; color:#6fbdbd; font-weight:600;">Always active — no opt-out available</p>
</div>

<div style="background:#f4f5f0; border-radius:12px; padding:28px 32px; margin-bottom:24px;">
  <h2 style="font-size:18px; font-weight:700; color:#081d34; margin:0 0 8px 0;">📊 Analytics Cookies</h2>
  <p style="margin:0 0 12px 0; font-size:14px;">Analytics cookies (such as Google Analytics) help us understand how visitors use our site, so we can improve the experience. The data is aggregated and does not identify you personally.</p>
  <p style="margin:0; font-size:14px;">To opt out of Google Analytics specifically, install the <a href="https://tools.google.com/dlpage/gaoptout" style="color:#6fbdbd;" target="_blank" rel="noopener">Google Analytics Opt-out Browser Add-on</a>.</p>
</div>

<div style="background:#f4f5f0; border-radius:12px; padding:28px 32px; margin-bottom:24px;">
  <h2 style="font-size:18px; font-weight:700; color:#081d34; margin:0 0 8px 0;">🎯 Marketing &amp; Advertising Cookies</h2>
  <p style="margin:0 0 12px 0; font-size:14px;">Marketing cookies allow us and our advertising partners to show you relevant ads on other websites and to measure the performance of ad campaigns. These require your explicit consent.</p>
  <p style="margin:0; font-size:14px;">To manage advertising preferences: <a href="https://adssettings.google.com" style="color:#6fbdbd;" target="_blank" rel="noopener">Google Ad Settings</a> &nbsp;|&nbsp; <a href="https://www.youronlinechoices.com" style="color:#6fbdbd;" target="_blank" rel="noopener">YourOnlineChoices.com</a></p>
</div>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<h2 style="font-size:20px; font-weight:700; color:#081d34; margin-bottom:12px;">Managing Cookies in Your Browser</h2>
<p>You can control and delete cookies through your browser settings at any time. Keep in mind that disabling certain cookies may affect how parts of the website function, including your basket and checkout.</p>
<ul style="padding-left:22px;">
  <li><a href="https://support.google.com/chrome/answer/95647" style="color:#6fbdbd;" target="_blank" rel="noopener">Google Chrome — Manage cookies</a></li>
  <li><a href="https://support.mozilla.org/en-US/kb/cookies-information-websites-store-on-your-computer" style="color:#6fbdbd;" target="_blank" rel="noopener">Mozilla Firefox — Manage cookies</a></li>
  <li><a href="https://support.apple.com/en-gb/guide/safari/sfri11471/mac" style="color:#6fbdbd;" target="_blank" rel="noopener">Apple Safari — Manage cookies</a></li>
  <li><a href="https://support.microsoft.com/en-gb/topic/delete-and-manage-cookies-168dab11-0753-043d-7c16-ede5947fc64d" style="color:#6fbdbd;" target="_blank" rel="noopener">Microsoft Edge — Manage cookies</a></li>
</ul>

<hr style="border:none;border-top:1px solid #eee;margin:36px 0;">

<p>For full details of all cookies we use and why, please read our <a href="/cookie/" style="color:#6fbdbd;">Cookie Policy</a>. For questions, email us at <a href="mailto:info@wookied.com" style="color:#6fbdbd;">info@wookied.com</a>.</p>

</div>
' ),
		'my-account' => array( 'title' => 'My account', 'menu' => '', 'content' => '[woocommerce_my_account]' ),
	);
}

function wookiee_sideload_theme_image( $filename, $title ) {
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

function wookiee_create_dummy_products() {
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
            'img'   => 'wookiee-prod-organizer.png',
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
                $attach_id = wookiee_sideload_theme_image( $prod['img'], $prod['title'] );
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
                $attach_id = wookiee_sideload_theme_image( $prod['img'], $prod['title'] );
                if ( $attach_id ) {
                    update_post_meta( $existing->ID, '_thumbnail_id', $attach_id );
                }
            }
        }
    }
}
