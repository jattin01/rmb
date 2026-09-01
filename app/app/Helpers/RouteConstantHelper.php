<?php

namespace App\Helpers;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class RouteConstantHelper
{
    const HOME = "orders.overview";
    const DASHBOARD = "dashboard.index";
    const DASHBOARD_ORDER_VOLUME_GRAPH = "dashboard.orderVolume.graph";
    const DASHBOARD_ORDER_GRAPH = "dashboard.order.graph";
    const DASHBOARD_ORDER_TRENDS_GRAPH = "dashboard.orderTrends.graph";
    const LOGIN = "auth.login.view";
    const FORGOT_PASSWORD = "auth.forgot.password.view";
    const FORGOT_PASSWORD_ACTION = "auth.forgot.password.submit";
    const FORGOT_PASSWORD_OTP_VERIFY = "auth.forgot.password.otp.submit";
    const FORGOT_PASSWORD_OTP_VERIFY_VIEW = "auth.forgot.password.otp.view";
    const FORGOT_PASSWORD_OTP_RESEND = "auth.forgot.password.otp.resend";
    const RESET_PASSWORD_ACTION = "auth.reset.password.submit";
    const RESET_PASSWORD_VIEW = "auth.reset.password.view";
    const LOGIN_ACTION = "auth.login.submit";
    const LOGOUT_ACTION = "auth.logout.submit";
    const ORDER_IMPORT = "orders.import";
    const ORDER_EXPORT = "orders.export";
    const ORDER_SCHEDULE_STEP_1 = "orders.schedule.step.one";
    const ORDER_SCHEDULE_STEP_2 = "orders.schedule.step.two";
    const ORDER_SCHEDULE_STEP_3 = "orders.schedule.step.three";
    const UPDATE_SELECTED_ORDERS = "orders.selected.update";
    const GENERATE_SCHEDULE = "orders.schedule.generate";
    const RESET_ORDERS = "orders.reset";
    const UPDATE_SINGLE_ORDER = "orders.single.update";
    const ORDER_SCHEDULE_VIEW = "orders.schedule.view";
    const PUBLISH_ORDERS = "orders.schedule.publish";
    const CUSTOMER_INDEX = "customers.index";
    const CUSTOMER_CREATE = "customers.create";
    const CUSTOMER_STORE = "customers.store";
    const CUSTOMER_EDIT = "customers.edit";
    const CUSTOMER_EXPORT = "customers.export";
    const CUSTOMER_CREATE_PROJECT = "customer.create_project";
    const CUSTOMER_STORE_PROJECT = "customer.store_project";
    const CUSTOMER_EDIT_PROJECT = "customers.edit_project";
    const PROJECT_SITE_ADDRESS_STORE = "customers.store_site_address";
    const CUSTOMER_STORE_PRODUCT = "customers.store_product";
    const CUSTOMER_EDIT_PROJECT_PRODUCT = "customers.edit_project_product";
    const CUSTOMER_DELETE_PROJECT_SITE = "customers.delete_project_site";
    const CUSTOMER_EDIT_PROJECT_SITE = "customers.edit_project_site";
    const CUSTOMER_DELETE_PROJECT_PRODUCT = "customers.delete_project_product";
    const SETTING_INDEX = "setting.index";
    const USERS_CREATE = "users.create";
    const USER_STORE = "users.store";
    const USER_EDIT = "users.edit";
    const LOCATION_CREATE = "location.create";
    const LOCATION_STORE = "location.store";
    const LOCATION_EDIT = "location.edit";
    const PROVINCES = "setting.provinces";
    const PRODUCT_DETAILS = "customer.product_details";

    const PRODUCT_INDEX = "products.index";
    const PRODUCT_CREATE = "products.create";
    const PRODUCT_STORE = "products.store";
    const PRODUCT_EDIT = "products.edit";
    const PRODUCT_CONTENT_STORE = "products.content_store";
    const PRODUCT_CONTENT_EDIT = "products.content_edit";
    const PRODUCT_TYPE_STORE = "products.type_store";
    const PRODUCT_TYPE_EDIT = "products.edit_type";

    const RESOURCES_INDEX = "resources.index";
    const RESOURCES_BATCHING_PLANT_CREATE = "resources.create_batching_plant";
    const RESOURCES_BATCHING_PLANT_STORE = "resources.store_batching_plant";
    const RESOURCES_BATCHING_PLANT_EDIT = "resources.edit_batching_plant";

    const RESOURCES_TRANSIT_MIXER_CREATE = "resources.transit_mixer_create";
    const RESOURCES_TRANSIT_MIXER_STORE = "resources.transit_mixer_store";
    const RESOURCES_TRANSIT_MIXER_EDIT = "resources.transit_mixer_edit";

    const RESOURCES_PUMP_CREATE = "resources.pump_create";
    const RESOURCES_PUMP_STORE = "resources.pump_store";
    const RESOURCES_PUMP_EDIT = "resources.pump_edit";

    const SETTINGS_HOME = "settings.home";

    const SETTINGS_CAPACITY = "settings.capacity.index";
    const SETTINGS_CAPACITY_CREATE = "settings.capacity.create";
    const SETTINGS_CAPACITY_EDIT= "settings.capacity.edit";
    const SETTINGS_CAPACITY_STORE = "settings.capacity.store";
    // const SETTINGS_CAPACITY_UPDATE = "settings.capacity.update";

    const BATCHING_DASHBOARD = "resources.dashboard";
    const BATCHING_DETAILS = "batching.details";

    const SETTINGS_BATCHING_PLANT_INDEX = "settings.batchingPlants.index";
    const SETTINGS_BATCHING_PLANT_CREATE = "settings.batchingPlants.create";
    const SETTINGS_BATCHING_PLANT_EDIT = "settings.batchingPlants.edit";
    const SETTINGS_BATCHING_PLANT_STORE = "settings.batchingPlants.store";
    const SETTINGS_BATCHING_PLANT_EXPORT = "settings.batchingPlants.export";
    const SETTINGS_TRANSIT_MIXER_INDEX = "settings.transitMixers.index";
    const SETTINGS_TRANSIT_MIXER_CREATE = "settings.transitMixers.create";
    const SETTINGS_TRANSIT_MIXER_EDIT = "settings.transitMixers.edit";
    const SETTINGS_TRANSIT_MIXER_STORE = "settings.transitMixers.store";
    const SETTINGS_TRANSIT_MIXER_EXPORT = "settings.transitMixers.export";
    const SETTINGS_PUMP_INDEX = "settings.pumps.index";
    const SETTINGS_PUMP_CREATE = "settings.pumps.create";
    const SETTINGS_PUMP_EDIT = "settings.pumps.edit";
    const SETTINGS_PUMP_STORE = "settings.pumps.store";
    const SETTINGS_PUMP_GET_SIZE = "settings.pumps.getSize";
    const SETTINGS_PUMP_GET_EXPORT = "settings.pumps.getExport";
    const SETTINGS_USER_INDEX = "settings.users.index";
    const SETTINGS_USER_CREATE = "settings.users.create";
    const SETTINGS_USER_EDIT = "settings.users.edit";
    const SETTINGS_USER_STORE = "settings.users.store";
    const SETTINGS_USER_EXPORT = "settings.users.export";

    const SETTINGS_USER_UPDATE_MY_PROFILE = "users.profile.update";
    const SETTINGS_COMPANY_LOCATIONS_INDEX = "settings.companyLocations.index";
    const SETTINGS_COMPANY_LOCATIONS_CREATE = "settings.companyLocations.create";
    const SETTINGS_COMPANY_LOCATIONS_EDIT = "settings.companyLocations.edit";
    const SETTINGS_COMPANY_LOCATIONS_STORE = "settings.companyLocations.store";
    const SETTINGS_COMPANY_LOCATIONS_GET_USERS = "settings.companyLocations.get.users";
    const SETTINGS_COMPANY_LOCATIONS_GET_EXPORT = "settings.companyLocations.get.export";
    const SETTINGS_PRODUCT_TYPES_INDEX = "settings.productTypes.index";
    const SETTINGS_PRODUCT_TYPES_CREATE = "settings.productTypes.create";
    const SETTINGS_PRODUCT_TYPES_EDIT = "settings.productTypes.edit";
    const SETTINGS_PRODUCT_TYPES_STORE = "settings.productTypes.store";
    const SETTINGS_PRODUCT_MIX_TYPE_EXPORT = "settings.productMixType.export";
    const SETTINGS_PRODUCT_TYPES_GET_BY_COMPANY = "settings.productTypes.get.by.company";
    const SETTINGS_PRODUCTS_INDEX = "settings.products.index";
    const SETTINGS_PRODUCTS_CREATE = "settings.products.create";
    const SETTINGS_PRODUCTS_EDIT = "settings.products.edit";
    const SETTINGS_PRODUCTS_STORE = "settings.products.store";
    const SETTINGS_PRODUCT_CONTENTS_STORE = "settings.product.contents.store";
    const SETTINGS_PRODUCT_CONTENTS_EDIT = "settings.product.contents.store";
    const SETTINGS_PRODUCT_CONTENTS_EXPORT = "settings.product.mix.export";
    const SETTINGS_CUSTOMER_TEAM_INDEX = "settings.customerTeam.index";
    const SETTINGS_CUSTOMER_TEAM_CREATE = "settings.customerTeam.create";
    const SETTINGS_CUSTOMER_TEAM_EDIT = "settings.customerTeam.edit";
    const SETTINGS_CUSTOMER_TEAM_STORE = "settings.customerTeam.store";
    const SETTINGS_CUSTOMER_TEAM_EXPORT = "settings.customerTeam.export";
    const GROUP_COMPANY_GET_LOCATIONS = "groupCompany.get.locations";
    const SETTINGS_CUSTOMER_PROJECTS_INDEX = "settings.customerProjects.index";
    const SETTINGS_CUSTOMER_PROJECTS_CREATE = "settings.customerProjects.create";
    const SETTINGS_CUSTOMER_PROJECTS_EDIT = "settings.customerProjects.edit";
    const SETTINGS_CUSTOMER_PROJECTS_STORE = "settings.customerProjects.store";
    const SETTINGS_CUSTOMER_PROJECTS_EXPORT = "settings.customerProjects.export";
    const SETTINGS_CUSTOMER_PROJECT_SITES_INDEX = "settings.customerProjectSites.index";
    const SETTINGS_CUSTOMER_PROJECT_SITES_EDIT = "settings.customerProjectSites.edit";
    const SETTINGS_CUSTOMER_PROJECT_SITES_EXPORT = "settings.customerProjectSites.export";

    const SETTINGS_CUSTOMER_PROJECT_SITES_STORE = "settings.customerProjectSites.store";
    const SETTINGS_CUSTOMER_PROJECT_PRODUCTS_INDEX = "settings.customerProjectProducts.index";
    const SETTINGS_CUSTOMER_PROJECT_PRODUCTS_EDIT = "settings.customerProjectProducts.edit";
    const SETTINGS_CUSTOMER_PROJECT_PRODUCTS_STORE = "settings.customerProjectProducts.store";
    const SETTINGS_CUSTOMER_PROJECT_PRODUCTS_DETAILS = "settings.customerProjectProducts.details";
    const SETTINGS_CUSTOMER_PROJECT_MIX_EXPORT = "settings.customerProjectMix.export";

    const SETTINGS_DRIVERS_INDEX = "settings.drivers.index";
    const SETTINGS_DRIVERS_EDIT = "settings.drivers.edit";
    const SETTINGS_DRIVERS_STORE = "settings.drivers.store";
    const SETTINGS_DRIVERS_EXPORT = "settings.drivers.export";
    const SETTINGS_DRIVERS_CREATE = "settings.drivers.create";
    const SETTINGS_ORDER_APPROVAL_INDEX = "settings.orderApproval.index";
    const SETTINGS_ORDER_APPROVAL_CREATE = "settings.orderApproval.create";
    const SETTINGS_ORDER_APPROVAL_EDIT = "settings.orderApproval.edit";
    const SETTINGS_ORDER_APPROVAL_STORE = "settings.orderApproval.store";
    const SETTINGS_ORDER_APPROVAL_EXPORT = "settings.orderApproval.export";
    const SETTINGS_STRUCTURE_INDEX = "settings.structure.index";
    const SETTINGS_STRUCTURE_CREATE = "settings.structure.create";
    const SETTINGS_STRUCTURE_EDIT = "settings.structure.edit";
    const SETTINGS_STRUCTURE_STORE = "settings.structure.store";
    const SETTINGS_STRUCTURE_EXPORT = "settings.structure.export";
    const SETTINGS_GLOBAL_INDEX = "settings.global.index";
    const SETTINGS_GLOBAL_CREATE = "settings.global.create";
    const SETTINGS_GLOBAL_EDIT = "settings.global.edit";
    const SETTINGS_GLOBAL_UPDATE = "settings.global.update";
    const SETTINGS_GLOBAL_STORE = "settings.global.store";

    const CHAT_ROOMS_INDEX = "chat.rooms.index";
    const CHAT_ROOMS_GET_IDS = "chat.rooms.get.ids";
    const CHAT_ROOMS_GET_DETAILS = "chat.rooms.details";
    const CHAT_ROOMS_GET_DRIVERS = "chat.rooms.driver.details";

}
