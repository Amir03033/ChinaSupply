<?php

return [
    'user_type' => ['buyer' => 'Buyer', 'supplier' => 'Supplier', 'staff' => 'Staff'],
    'staff_role' => ['administrator' => 'Administrator', 'sourcing_manager' => 'Sourcing manager', 'verification_specialist' => 'Verification specialist'],
    'publication_status' => ['draft' => 'Draft', 'active' => 'Active', 'inactive' => 'Inactive'],
    'contact_topic' => ['sourcing' => 'Sourcing request', 'services' => 'Question about services and pricing', 'supplier' => 'Register as a supplier', 'general' => 'General question'],
    'enquiry_status' => ['new' => 'New', 'in_progress' => 'In progress', 'closed' => 'Closed'],
    'pipeline_status' => ['discovered' => 'Discovered', 'researching' => 'Researching', 'applied' => 'Self-registered', 'contacted' => 'Contacted', 'responded' => 'Responded', 'interested' => 'Interested', 'documents_requested' => 'Documents requested', 'verification_pending' => 'Verification pending', 'verified' => 'Verified', 'published' => 'Published', 'rejected' => 'Rejected', 'inactive' => 'Inactive'],
    'supplier_source' => ['alibaba' => 'Alibaba', '1688' => '1688', 'global_sources' => 'Global Sources', 'made_in_china' => 'Made-in-China', 'canton_fair' => 'Canton Fair', 'factory_website' => 'Factory website', 'directory' => 'Industry directory', 'referral' => 'Referral', 'self_registration' => 'Self-registration', 'other' => 'Other'],
    'company_type' => ['manufacturer' => 'Manufacturer', 'trading_company' => 'Trading company', 'hybrid' => 'Manufacturer and trader', 'unknown' => 'Not yet determined'],
    'check_status' => ['not_checked' => 'Not checked', 'pending' => 'Pending review', 'passed' => 'Passed', 'failed' => 'Failed', 'expired' => 'Expired'],
    'document_type' => ['business_license' => 'Business licence', 'export_license' => 'Export licence', 'certificate' => 'Certificate', 'audit_report' => 'Audit report', 'factory_photo' => 'Factory photo', 'product_catalog' => 'Product catalogue', 'evidence' => 'Verification evidence', 'other' => 'Other'],
    'document_status' => ['pending' => 'Under review', 'approved' => 'Approved', 'rejected' => 'Rejected'],
    'channel' => ['email' => 'Email', 'wechat' => 'WeChat', 'whatsapp' => 'WhatsApp', 'phone' => 'Phone', 'video_call' => 'Video call', 'marketplace_chat' => 'Marketplace chat', 'meeting' => 'Meeting', 'other' => 'Other'],
];
