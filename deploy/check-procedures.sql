-- Run after importing procedures-online.sql in the SAME selected database.
-- Success: no rows returned (no missing procedures).
SELECT expected.procedure_name AS missing_procedure
FROM (
SELECT 'sp_admin_user_find' AS procedure_name
UNION ALL
SELECT 'sp_admin_user_status' AS procedure_name
UNION ALL
SELECT 'sp_admin_users_count' AS procedure_name
UNION ALL
SELECT 'sp_admin_users_list' AS procedure_name
UNION ALL
SELECT 'sp_agent_property_add_amenity' AS procedure_name
UNION ALL
SELECT 'sp_agent_property_by_id' AS procedure_name
UNION ALL
SELECT 'sp_agent_property_create' AS procedure_name
UNION ALL
SELECT 'sp_agent_property_delete' AS procedure_name
UNION ALL
SELECT 'sp_agent_property_list' AS procedure_name
UNION ALL
SELECT 'sp_agent_property_status' AS procedure_name
UNION ALL
SELECT 'sp_agent_property_update' AS procedure_name
UNION ALL
SELECT 'sp_appointment_agent_status' AS procedure_name
UNION ALL
SELECT 'sp_appointment_book' AS procedure_name
UNION ALL
SELECT 'sp_appointment_customer_cancel' AS procedure_name
UNION ALL
SELECT 'sp_appointment_for_agent' AS procedure_name
UNION ALL
SELECT 'sp_appointment_for_customer' AS procedure_name
UNION ALL
SELECT 'sp_appointment_has_active' AS procedure_name
UNION ALL
SELECT 'sp_catalog_admin_list' AS procedure_name
UNION ALL
SELECT 'sp_catalog_children' AS procedure_name
UNION ALL
SELECT 'sp_catalog_create' AS procedure_name
UNION ALL
SELECT 'sp_catalog_delete' AS procedure_name
UNION ALL
SELECT 'sp_catalog_find' AS procedure_name
UNION ALL
SELECT 'sp_catalog_name_exists' AS procedure_name
UNION ALL
SELECT 'sp_catalog_sorted' AS procedure_name
UNION ALL
SELECT 'sp_catalog_update' AS procedure_name
UNION ALL
SELECT 'sp_catalog_usage' AS procedure_name
UNION ALL
SELECT 'sp_count_search_properties_v2' AS procedure_name
UNION ALL
SELECT 'sp_count_search_properties_v3' AS procedure_name
UNION ALL
SELECT 'sp_dashboard_by_district' AS procedure_name
UNION ALL
SELECT 'sp_dashboard_by_province' AS procedure_name
UNION ALL
SELECT 'sp_dashboard_by_status' AS procedure_name
UNION ALL
SELECT 'sp_dashboard_counts' AS procedure_name
UNION ALL
SELECT 'sp_favorite_add' AS procedure_name
UNION ALL
SELECT 'sp_favorite_count' AS procedure_name
UNION ALL
SELECT 'sp_favorite_exists' AS procedure_name
UNION ALL
SELECT 'sp_favorite_ids' AS procedure_name
UNION ALL
SELECT 'sp_favorite_list' AS procedure_name
UNION ALL
SELECT 'sp_favorite_remove' AS procedure_name
UNION ALL
SELECT 'sp_get_agent_properties' AS procedure_name
UNION ALL
SELECT 'sp_latest_properties' AS procedure_name
UNION ALL
SELECT 'sp_latest_properties_v3' AS procedure_name
UNION ALL
SELECT 'sp_location_districts' AS procedure_name
UNION ALL
SELECT 'sp_location_province_by_district' AS procedure_name
UNION ALL
SELECT 'sp_location_provinces' AS procedure_name
UNION ALL
SELECT 'sp_location_valid_ward' AS procedure_name
UNION ALL
SELECT 'sp_location_wards' AS procedure_name
UNION ALL
SELECT 'sp_location_wards_for_provinces' AS procedure_name
UNION ALL
SELECT 'sp_model_amenities_all' AS procedure_name
UNION ALL
SELECT 'sp_model_amenities_create' AS procedure_name
UNION ALL
SELECT 'sp_model_amenities_delete' AS procedure_name
UNION ALL
SELECT 'sp_model_amenities_find' AS procedure_name
UNION ALL
SELECT 'sp_model_amenities_update' AS procedure_name
UNION ALL
SELECT 'sp_model_districts_all' AS procedure_name
UNION ALL
SELECT 'sp_model_districts_create' AS procedure_name
UNION ALL
SELECT 'sp_model_districts_delete' AS procedure_name
UNION ALL
SELECT 'sp_model_districts_find' AS procedure_name
UNION ALL
SELECT 'sp_model_districts_update' AS procedure_name
UNION ALL
SELECT 'sp_model_favorites_all' AS procedure_name
UNION ALL
SELECT 'sp_model_favorites_create' AS procedure_name
UNION ALL
SELECT 'sp_model_notifications_all' AS procedure_name
UNION ALL
SELECT 'sp_model_notifications_create' AS procedure_name
UNION ALL
SELECT 'sp_model_notifications_delete' AS procedure_name
UNION ALL
SELECT 'sp_model_notifications_find' AS procedure_name
UNION ALL
SELECT 'sp_model_notifications_update' AS procedure_name
UNION ALL
SELECT 'sp_model_password_resets_all' AS procedure_name
UNION ALL
SELECT 'sp_model_password_resets_create' AS procedure_name
UNION ALL
SELECT 'sp_model_properties_all' AS procedure_name
UNION ALL
SELECT 'sp_model_properties_create' AS procedure_name
UNION ALL
SELECT 'sp_model_properties_delete' AS procedure_name
UNION ALL
SELECT 'sp_model_properties_find' AS procedure_name
UNION ALL
SELECT 'sp_model_properties_update' AS procedure_name
UNION ALL
SELECT 'sp_model_property_amenities_all' AS procedure_name
UNION ALL
SELECT 'sp_model_property_amenities_create' AS procedure_name
UNION ALL
SELECT 'sp_model_property_images_all' AS procedure_name
UNION ALL
SELECT 'sp_model_property_images_create' AS procedure_name
UNION ALL
SELECT 'sp_model_property_images_delete' AS procedure_name
UNION ALL
SELECT 'sp_model_property_images_find' AS procedure_name
UNION ALL
SELECT 'sp_model_property_images_update' AS procedure_name
UNION ALL
SELECT 'sp_model_property_types_all' AS procedure_name
UNION ALL
SELECT 'sp_model_property_types_create' AS procedure_name
UNION ALL
SELECT 'sp_model_property_types_delete' AS procedure_name
UNION ALL
SELECT 'sp_model_property_types_find' AS procedure_name
UNION ALL
SELECT 'sp_model_property_types_update' AS procedure_name
UNION ALL
SELECT 'sp_model_provinces_all' AS procedure_name
UNION ALL
SELECT 'sp_model_provinces_create' AS procedure_name
UNION ALL
SELECT 'sp_model_provinces_delete' AS procedure_name
UNION ALL
SELECT 'sp_model_provinces_find' AS procedure_name
UNION ALL
SELECT 'sp_model_provinces_update' AS procedure_name
UNION ALL
SELECT 'sp_model_reports_all' AS procedure_name
UNION ALL
SELECT 'sp_model_reports_create' AS procedure_name
UNION ALL
SELECT 'sp_model_reports_delete' AS procedure_name
UNION ALL
SELECT 'sp_model_reports_find' AS procedure_name
UNION ALL
SELECT 'sp_model_reports_update' AS procedure_name
UNION ALL
SELECT 'sp_model_reviews_all' AS procedure_name
UNION ALL
SELECT 'sp_model_reviews_create' AS procedure_name
UNION ALL
SELECT 'sp_model_reviews_delete' AS procedure_name
UNION ALL
SELECT 'sp_model_reviews_find' AS procedure_name
UNION ALL
SELECT 'sp_model_reviews_update' AS procedure_name
UNION ALL
SELECT 'sp_model_roles_all' AS procedure_name
UNION ALL
SELECT 'sp_model_roles_create' AS procedure_name
UNION ALL
SELECT 'sp_model_roles_delete' AS procedure_name
UNION ALL
SELECT 'sp_model_roles_find' AS procedure_name
UNION ALL
SELECT 'sp_model_roles_update' AS procedure_name
UNION ALL
SELECT 'sp_model_saved_searches_all' AS procedure_name
UNION ALL
SELECT 'sp_model_saved_searches_create' AS procedure_name
UNION ALL
SELECT 'sp_model_saved_searches_delete' AS procedure_name
UNION ALL
SELECT 'sp_model_saved_searches_find' AS procedure_name
UNION ALL
SELECT 'sp_model_saved_searches_update' AS procedure_name
UNION ALL
SELECT 'sp_model_users_all' AS procedure_name
UNION ALL
SELECT 'sp_model_users_create' AS procedure_name
UNION ALL
SELECT 'sp_model_users_delete' AS procedure_name
UNION ALL
SELECT 'sp_model_users_find' AS procedure_name
UNION ALL
SELECT 'sp_model_users_update' AS procedure_name
UNION ALL
SELECT 'sp_model_viewing_appointments_all' AS procedure_name
UNION ALL
SELECT 'sp_model_viewing_appointments_create' AS procedure_name
UNION ALL
SELECT 'sp_model_viewing_appointments_delete' AS procedure_name
UNION ALL
SELECT 'sp_model_viewing_appointments_find' AS procedure_name
UNION ALL
SELECT 'sp_model_viewing_appointments_update' AS procedure_name
UNION ALL
SELECT 'sp_model_wards_all' AS procedure_name
UNION ALL
SELECT 'sp_model_wards_create' AS procedure_name
UNION ALL
SELECT 'sp_model_wards_delete' AS procedure_name
UNION ALL
SELECT 'sp_model_wards_find' AS procedure_name
UNION ALL
SELECT 'sp_model_wards_update' AS procedure_name
UNION ALL
SELECT 'sp_moderate_property' AS procedure_name
UNION ALL
SELECT 'sp_moderation_apply' AS procedure_name
UNION ALL
SELECT 'sp_moderation_count' AS procedure_name
UNION ALL
SELECT 'sp_moderation_list' AS procedure_name
UNION ALL
SELECT 'sp_moderation_page' AS procedure_name
UNION ALL
SELECT 'sp_moderation_total' AS procedure_name
UNION ALL
SELECT 'sp_property_detail' AS procedure_name
UNION ALL
SELECT 'sp_property_detail_amenities' AS procedure_name
UNION ALL
SELECT 'sp_property_detail_images' AS procedure_name
UNION ALL
SELECT 'sp_property_image_create' AS procedure_name
UNION ALL
SELECT 'sp_property_images' AS procedure_name
UNION ALL
SELECT 'sp_property_images_delete' AS procedure_name
UNION ALL
SELECT 'sp_property_images_remove_ids' AS procedure_name
UNION ALL
SELECT 'sp_property_increment_views' AS procedure_name
UNION ALL
SELECT 'sp_property_map' AS procedure_name
UNION ALL
SELECT 'sp_property_same_type' AS procedure_name
UNION ALL
SELECT 'sp_property_similar_full' AS procedure_name
UNION ALL
SELECT 'sp_property_similar_ranked' AS procedure_name
UNION ALL
SELECT 'sp_report_count' AS procedure_name
UNION ALL
SELECT 'sp_report_create' AS procedure_name
UNION ALL
SELECT 'sp_report_exists' AS procedure_name
UNION ALL
SELECT 'sp_report_list' AS procedure_name
UNION ALL
SELECT 'sp_report_page_full' AS procedure_name
UNION ALL
SELECT 'sp_report_resolve' AS procedure_name
UNION ALL
SELECT 'sp_report_resolve_full' AS procedure_name
UNION ALL
SELECT 'sp_report_submit_full' AS procedure_name
UNION ALL
SELECT 'sp_report_total_full' AS procedure_name
UNION ALL
SELECT 'sp_review_by_user' AS procedure_name
UNION ALL
SELECT 'sp_review_delete' AS procedure_name
UNION ALL
SELECT 'sp_review_for_property' AS procedure_name
UNION ALL
SELECT 'sp_review_save' AS procedure_name
UNION ALL
SELECT 'sp_review_stats' AS procedure_name
UNION ALL
SELECT 'sp_rt_admin_controller_reports_1' AS procedure_name
UNION ALL
SELECT 'sp_rt_admin_controller_reports_2' AS procedure_name
UNION ALL
SELECT 'sp_rt_admin_controller_reports_3' AS procedure_name
UNION ALL
SELECT 'sp_rt_admin_controller_reports_4' AS procedure_name
UNION ALL
SELECT 'sp_rt_admin_stats_properties_by_province_1' AS procedure_name
UNION ALL
SELECT 'sp_rt_agent_controller_validate_listing_input_1' AS procedure_name
UNION ALL
SELECT 'sp_rt_agent_controller_validate_listing_input_2' AS procedure_name
UNION ALL
SELECT 'sp_rt_appointment_find_with_property_1' AS procedure_name
UNION ALL
SELECT 'sp_rt_location_districts_by_province_1' AS procedure_name
UNION ALL
SELECT 'sp_rt_location_is_valid_ward_1' AS procedure_name
UNION ALL
SELECT 'sp_rt_location_is_valid_ward_2' AS procedure_name
UNION ALL
SELECT 'sp_rt_location_provinces_1' AS procedure_name
UNION ALL
SELECT 'sp_rt_location_search_administrative_1' AS procedure_name
UNION ALL
SELECT 'sp_rt_location_search_administrative_2' AS procedure_name
UNION ALL
SELECT 'sp_rt_location_wards_by_district_1' AS procedure_name
UNION ALL
SELECT 'sp_rt_location_wards_by_province_1' AS procedure_name
UNION ALL
SELECT 'sp_rt_notification_count_unread_for_user_1' AS procedure_name
UNION ALL
SELECT 'sp_rt_notification_create_1' AS procedure_name
UNION ALL
SELECT 'sp_rt_notification_delete_1' AS procedure_name
UNION ALL
SELECT 'sp_rt_notification_find_1' AS procedure_name
UNION ALL
SELECT 'sp_rt_notification_find_for_user_1' AS procedure_name
UNION ALL
SELECT 'sp_rt_notification_get_all_for_user_1' AS procedure_name
UNION ALL
SELECT 'sp_rt_notification_get_unread_for_user_1' AS procedure_name
UNION ALL
SELECT 'sp_rt_notification_mark_all_as_read_1' AS procedure_name
UNION ALL
SELECT 'sp_rt_notification_mark_as_read_1' AS procedure_name
UNION ALL
SELECT 'sp_rt_notification_mark_as_read_2' AS procedure_name
UNION ALL
SELECT 'sp_rt_property_amenity_ids_for_listing_1' AS procedure_name
UNION ALL
SELECT 'sp_rt_property_find_owned_1' AS procedure_name
UNION ALL
SELECT 'sp_rt_property_moderation_toggle_verified_1' AS procedure_name
UNION ALL
SELECT 'sp_rt_property_moderation_toggle_verified_2' AS procedure_name
UNION ALL
SELECT 'sp_rt_property_moderation_update_status_1' AS procedure_name
UNION ALL
SELECT 'sp_rt_property_moderation_update_status_2' AS procedure_name
UNION ALL
SELECT 'sp_rt_property_moderation_update_status_3' AS procedure_name
UNION ALL
SELECT 'sp_rt_property_moderation_update_status_4' AS procedure_name
UNION ALL
SELECT 'sp_rt_property_moderation_update_status_5' AS procedure_name
UNION ALL
SELECT 'sp_rt_property_moderation_update_status_6' AS procedure_name
UNION ALL
SELECT 'sp_rt_property_moderation_update_status_7' AS procedure_name
UNION ALL
SELECT 'sp_rt_property_renew_owned_listing_1' AS procedure_name
UNION ALL
SELECT 'sp_rt_property_sync_amenities_1' AS procedure_name
UNION ALL
SELECT 'sp_rt_property_sync_amenities_2' AS procedure_name
UNION ALL
SELECT 'sp_rt_property_update_owned_listing_1' AS procedure_name
UNION ALL
SELECT 'sp_rt_property_update_owned_listing_2' AS procedure_name
UNION ALL
SELECT 'sp_rt_property_update_owned_listing_3' AS procedure_name
UNION ALL
SELECT 'sp_rt_property_update_owned_listing_4' AS procedure_name
UNION ALL
SELECT 'sp_rt_property_update_owned_listing_5' AS procedure_name
UNION ALL
SELECT 'sp_rt_property_update_owned_listing_6' AS procedure_name
UNION ALL
SELECT 'sp_rt_property_update_owned_listing_7' AS procedure_name
UNION ALL
SELECT 'sp_rt_province_admin_list_1' AS procedure_name
UNION ALL
SELECT 'sp_rt_province_child_count_1' AS procedure_name
UNION ALL
SELECT 'sp_rt_province_usage_count_1' AS procedure_name
UNION ALL
SELECT 'sp_rt_report_controller_submit_1' AS procedure_name
UNION ALL
SELECT 'sp_rt_report_get_detail_1' AS procedure_name
UNION ALL
SELECT 'sp_rt_user_create_password_reset_token_1' AS procedure_name
UNION ALL
SELECT 'sp_rt_user_create_password_reset_token_2' AS procedure_name
UNION ALL
SELECT 'sp_rt_user_delete_password_reset_token_1' AS procedure_name
UNION ALL
SELECT 'sp_rt_user_delete_password_resets_for_email_1' AS procedure_name
UNION ALL
SELECT 'sp_rt_user_find_valid_password_reset_1' AS procedure_name
UNION ALL
SELECT 'sp_rt_ward_create_1' AS procedure_name
UNION ALL
SELECT 'sp_rt_ward_delete_1' AS procedure_name
UNION ALL
SELECT 'sp_rt_ward_find_1' AS procedure_name
UNION ALL
SELECT 'sp_rt_ward_find_by_province_id_1' AS procedure_name
UNION ALL
SELECT 'sp_rt_ward_update_1' AS procedure_name
UNION ALL
SELECT 'sp_rt_ward_usage_count_1' AS procedure_name
UNION ALL
SELECT 'sp_saved_search_create' AS procedure_name
UNION ALL
SELECT 'sp_saved_search_delete' AS procedure_name
UNION ALL
SELECT 'sp_saved_search_find' AS procedure_name
UNION ALL
SELECT 'sp_saved_search_list' AS procedure_name
UNION ALL
SELECT 'sp_search_properties' AS procedure_name
UNION ALL
SELECT 'sp_search_properties_v2' AS procedure_name
UNION ALL
SELECT 'sp_search_properties_v3' AS procedure_name
UNION ALL
SELECT 'sp_set_property_status' AS procedure_name
UNION ALL
SELECT 'sp_stats_properties_by_district' AS procedure_name
UNION ALL
SELECT 'sp_stats_properties_by_status' AS procedure_name
UNION ALL
SELECT 'sp_user_by_email' AS procedure_name
UNION ALL
SELECT 'sp_user_by_id' AS procedure_name
UNION ALL
SELECT 'sp_user_profile_full' AS procedure_name
UNION ALL
SELECT 'sp_user_register' AS procedure_name
UNION ALL
SELECT 'sp_user_session' AS procedure_name
UNION ALL
SELECT 'sp_user_set_password' AS procedure_name
UNION ALL
SELECT 'sp_user_update_profile' AS procedure_name
UNION ALL
SELECT 'sp_ward_admin_list' AS procedure_name
UNION ALL
SELECT 'sp_ward_name_exists' AS procedure_name
) AS expected
LEFT JOIN information_schema.ROUTINES AS actual
 ON actual.ROUTINE_SCHEMA=DATABASE() AND actual.ROUTINE_TYPE='PROCEDURE'
 AND actual.ROUTINE_NAME=expected.procedure_name
WHERE actual.ROUTINE_NAME IS NULL
ORDER BY expected.procedure_name;
