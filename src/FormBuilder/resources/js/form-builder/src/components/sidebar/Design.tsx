import {__} from "@wordpress/i18n";
import {
    CustomStyleSettings,
    DonateButtonSettings,
    DonationGoalSettings,
    FormDesignSettings
} from "@givewp/form-builder/settings";

const Sidebar = () => {
    return (
        <div
            id="sidebar-primary"
            className="givewp-next-gen-sidebar givewp-next-gen-sidebar-primary"
            role="region"
            aria-label={__('Form design settings')}
            tabIndex={-1}
        >
            <FormDesignSettings />
            <DonateButtonSettings />
            <DonationGoalSettings />
            <CustomStyleSettings />
        </div>
    )
}

export default Sidebar;
