/**
 * SuiteCRM is a customer relationship management program developed by SalesAgility Ltd.
 * Copyright (C) 2024 SalesAgility Ltd.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SALESAGILITY, SALESAGILITY DISCLAIMS THE
 * WARRANTY OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License
 * version 3, these Appropriate Legal Notices must retain the display of the
 * "Supercharged by SuiteCRM" logo. If the display of the logos is not reasonably
 * feasible for technical reasons, the Appropriate Legal Notices must display
 * the words "Supercharged by SuiteCRM".
 */

import {Component, OnInit, signal, WritableSignal} from "@angular/core";
import {AuthService} from "../../../../services/auth/auth.service";
import {Router} from "@angular/router";
import {MessageService} from "../../../../services/message/message.service";
import {isTrue} from "common";
import {LanguageStore} from "../../../../store/language/language.store";
import {ButtonCallback, ButtonInterface} from "../../../../common/components/button/button.model";
import {UserPreferenceStore} from "../../../../store/user-preference/user-preference.store";
import {Clipboard} from '@angular/cdk/clipboard';


@Component({
    selector: 'scrm-2fa',
    templateUrl: './2fa.component.html',
    styleUrls: [],
})
export class TwoFactorComponent implements OnInit {

    qrCodeUrl: string;
    qrCodeSvg: string;
    backupCodes: any;
    authCode: string;
    isAppMethodEnabled: WritableSignal<boolean> = signal(false);
    areRecoveryCodesGenerated: WritableSignal<boolean> = signal(false);
    isQrCodeGenerated: WritableSignal<boolean> = signal(false);

    title: string = '';
    appMethodHeaderLabel: string = '';
    enableAppMethodButtonConfig: ButtonInterface;
    disableAppMethodTButtonConfig: ButtonInterface;
    recoveryCodesHeaderLabel: string = '';

    constructor(
        protected authService: AuthService,
        protected router: Router,
        protected message: MessageService,
        protected language: LanguageStore,
        protected userPreference: UserPreferenceStore,
        protected clipboard: Clipboard
    ) {
    }

    ngOnInit() {
        this.title = this.language.getAppString('LBL_TWO_FACTOR_AUTH');
        this.appMethodHeaderLabel = this.language.getAppString('LBL_TWO_FACTOR_AUTH_APP_METHOD');
        this.recoveryCodesHeaderLabel = this.language.getAppString('LBL_BACKUP_CODES');

        const isEnabled = this.userPreference.getUserPreference('is_two_factor_enabled') ?? false;

        this.isAppMethodEnabled.set(isEnabled);
        this.areRecoveryCodesGenerated.set(isEnabled);

        this.enableAppMethodButtonConfig = {
            klass: 'btn btn-sm btn-main',
            onClick: ((): void => {
                this.enable2FactorAuth()
            }) as ButtonCallback,
            labelKey: 'LBL_ENABLE',
            titleKey: ''
        } as ButtonInterface;

        this.disableAppMethodTButtonConfig = {
            klass: 'btn btn-sm btn-main',
            onClick: ((): void => {
                this.disable2FactorAuth()
            }) as ButtonCallback,
            labelKey: 'LBL_DISABLE',
            titleKey: ''
        } as ButtonInterface;
    }

    public enable2FactorAuth(): void {

        this.authService.enable2fa().subscribe({
            next: (response) => {
                this.qrCodeUrl = response?.url;
                this.qrCodeSvg = response?.svg;
                this.backupCodes = response?.backupCodes;
                this.areRecoveryCodesGenerated.set(true);
                this.isQrCodeGenerated.set(true);
            },
            error: () => {
                this.isAppMethodEnabled.set(false);
                this.areRecoveryCodesGenerated.set(false);
            }
        });
    }

    public disable2FactorAuth(): void {
        this.authService.disable2fa().subscribe({
            next: (response) => {
                this.isAppMethodEnabled.set(false);
                this.areRecoveryCodesGenerated.set(false);
                this.isQrCodeGenerated.set(false);
            },
            error: () => {
                this.isAppMethodEnabled.set(true);
                this.areRecoveryCodesGenerated.set(true);
            }
        });
        return;
    }

    getTitle(): string {
        return this.title;
    }

    public finalize2fa() {
        this.authService.finalize2fa(this.authCode).subscribe(response => {
            const verified = response?.two_factor_setup_complete ?? false;

            if (isTrue(verified)) {
                this.message.addSuccessMessageByKey('LBL_FACTOR_AUTH_SUCCESS');

                this.isAppMethodEnabled.set(true);
                this.isQrCodeGenerated.set(false);
                this.authCode = '';

                return;
            }

            this.message.addDangerMessageByKey('LBL_FACTOR_AUTH_FAIL');
        })
    }

    public copyBackupCodes() {
        this.clipboard.copy(this.backupCodes);
    }
}
