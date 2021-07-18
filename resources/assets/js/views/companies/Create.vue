<template>
  <base-page>
    <!-- Page Header -->
    <sw-page-header :title="pageTitle" class="mb-3">
      <sw-breadcrumb slot="breadcrumbs">
        <sw-breadcrumb-item :title="$t('general.home')" to="/admin/dashboard" />
        <sw-breadcrumb-item :title="$tc('companies.item', 2)" to="/admin/companies" />
        <sw-breadcrumb-item
          v-if="$route.name === 'companies.edit'"
          :title="$t('companies.edit_item')"
          to="#"
          active
        />
        <sw-breadcrumb-item
          v-else
          :title="$t('companies.new_item')"
          to="#"
          active
        />
      </sw-breadcrumb>
    </sw-page-header>

    <div class="grid grid-cols-12">
      <div class="col-span-12 md:col-span-6">
        <form action="" @submit.prevent="submitItem">
          <sw-card>
            <sw-input-group
              :label="$t('companies.name')"
              :error="nameError"
              class="mb-4"
              required
            >
              <sw-input
                v-model.trim="formData.name"
                :invalid="$v.formData.name.$error"
                class="mt-2"
                focus
                type="text"
                name="name"
                @input="$v.formData.name.$touch()"
              />
            </sw-input-group>

            <sw-input-group
              :label="$t('companies.domain')"
              :error="domainError"
              class="mb-4"
              required
            >
              <sw-input
                v-model.trim="formData.domain"
                :invalid="$v.formData.domain.$error"
                class="mt-2"
                focus
                type="text"
                name="domain"
                @input="$v.formData.domain.$touch()"
              />
            </sw-input-group>

            <sw-input-group
              :label="$t('companies.email')"
              :error="emailError"
              class="mb-4"
              required
            >
              <sw-input
                v-model.trim="formData.email"
                :invalid="$v.formData.email.$error"
                class="mt-2"
                focus
                type="text"
                name="email"
                @input="$v.formData.email.$touch()"
              />
            </sw-input-group>

            <sw-input-group
              :label="$t('companies.address')"
              :error="addressError"
              class="mb-4"
              required
            >
              <sw-textarea
                v-model.trim="formData.address"
                :invalid="$v.formData.address.$error"
                class="mt-2"
                focus
                type="text"
                name="address"
                @input="$v.formData.address.$touch()"
              />
            </sw-input-group>

            <sw-input-group
            :label="$tc('settings.preferences.currency')"
            :error="currencyError"
            class="mb-4"
            required
            >
            <sw-select
                v-model="formData.currency"
                :options="currencies"
                :custom-label="currencyNameWithCode"
                :class="{ error: $v.formData.currency.$error }"
                :searchable="true"
                :show-labels="false"
                :allow-empty="false"
                :placeholder="$tc('settings.currencies.select_currency')"
                class="mt-2"
                label="name"
                track-by="id"
            />
            </sw-input-group>

            <sw-input-group :label="$tc('settings.company_info.company_logo')">
            <div
                id="logo-box"
                class="relative flex items-center justify-center h-24 p-5 mt-2 mb-4 bg-transparent border-2 border-gray-200 border-dashed rounded-md image-upload-box"
            >
                <img
                v-if="previewLogo"
                :src="previewLogo"
                class="absolute opacity-100 preview-logo"
                style="max-height: 80%; animation: fadeIn 2s ease"
                />
                <div v-else class="flex flex-col items-center">
                <cloud-upload-icon
                    class="h-5 mb-2 text-xl leading-6 text-gray-400"
                />
                <p class="text-xs leading-4 text-center text-gray-400">
                    Drag a file here or
                    <span id="pick-avatar" class="cursor-pointer text-primary-500">
                    browse
                    </span>
                    to choose a file
                </p>
                </div>
            </div>

            <sw-avatar
                :preview-avatar="previewLogo"
                trigger="#logo-box"
                @changed="onChange"
                @uploadHandler="onUploadHandler"
                @handleUploadError="onHandleUploadError"
            >
                <template v-slot:icon>
                <cloud-upload-icon
                    class="h-5 mb-2 text-xl leading-6 text-gray-400"
                />
                </template>
            </sw-avatar>
            </sw-input-group>

            <div class="mb-4">
              <sw-button
                :loading="isLoading"
                variant="primary"
                size="lg"
                class="flex justify-center w-full md:w-auto"
              >
                <save-icon v-if="!isLoading" class="mr-2 -ml-1" />
                {{ isEdit ? $t('companies.update_company') : $t('companies.save_company') }}
              </sw-button>
            </div>
          </sw-card>
        </form>
      </div>
    </div>
  </base-page>
</template>

<script>
import { mapActions, mapGetters } from 'vuex'
import { CloudUploadIcon } from '@vue-hero-icons/solid'
import { ShoppingCartIcon } from '@vue-hero-icons/solid'
import TheSiteHeaderVue from '../layouts/partials/TheSiteHeader.vue'
const {
  required,
  minLength,
  email,
  numeric,
  minValue,
  maxLength,
} = require('vuelidate/lib/validators')

export default {
  components: {
    ShoppingCartIcon,
    CloudUploadIcon,
  },

  data() {
    return {
      isLoading: false,
      title: 'Add Item',
      units: [],
      taxes: [],
      taxPerItem: '',
      previewLogo: null,
      fileObject: null,
      cropperOutputMime: '',

      formData: {
        name: '',
        logo: '',
        domain: '',
        email: '',
        address: '',
        currency: '',
      },
    }
  },

  computed: {

    ...mapGetters([
      'currencies',
      'timeZones',
      'dateFormats',
      'fiscalYears',
      'languages',
    ]),

    ...mapGetters('company', ['defaultCurrencyForInput']),

    ...mapGetters('item', ['itemUnits']),

    ...mapGetters('taxType', ['taxTypes']),

    price: {
      get: function () {
        return this.formData.price / 100
      },
      set: function (newValue) {
        this.formData.price = Math.round(newValue * 100)
      },
    },

    pageTitle() {
      if (this.$route.name === 'companies.edit') {
        return this.$t('companies.edit_item')
      }
      return this.$t('companies.new_item')
    },

    ...mapGetters('taxType', ['taxTypes']),

    isEdit() {
      if (this.$route.name === 'companies.edit') {
        return true
      }
      return false
    },

    nameError() {
      if (!this.$v.formData.name.$error) {
        return ''
      }

      if (!this.$v.formData.name.required) {
        return this.$t('validation.required')
      }

      if (!this.$v.formData.name.minLength) {
        return this.$tc(
          'validation.name_min_length',
          this.$v.formData.name.$params.minLength.min,
          { count: this.$v.formData.name.$params.minLength.min }
        )
      }
    },

    logoError() {
      if (!this.$v.formData.logo.$error) {
        return ''
      }

      if (!this.$v.formData.logo.minLength) {
        return this.$t('validation.required')
      }
    },

    domainError() {
      if (!this.$v.formData.domain.$error) {
        return ''
      }

      if (!this.$v.formData.domain.required) {
        return this.$t('validation.required')
      }
      
    },

    emailError() {
      if (!this.$v.formData.email.$error) {
        return ''
      }

      if (!this.$v.formData.email.required) {
        return this.$t('validation.required')
      }

      if (!this.$v.formData.email.email) {
        return this.$t('validation.email_incorrect')
      }
      
    },

    addressError() {
      if (!this.$v.formData.address.$error) {
        return ''
      }

      if (!this.$v.formData.address.required) {
        return this.$t('validation.required')
      }
    },

    currencyError() {
      if (!this.$v.formData.currency.$error) {
        return ''
      }
      if (!this.$v.formData.currency.required) {
        return this.$tc('validation.required')
      }
    },
  },

  created() {
    this.loadData()
  },

  mounted() {
    this.$v.formData.$reset()
  },

  validations: {
    formData: {
      name: {
        required,
        minLength: minLength(3),
      },
      domain: {
        required,
      },
      email: {
        required,
        email,
      },
      address: {
        required,
      },
      currency: {
        required,
      },
    },
  },

  methods: {
    ...mapActions('item', [
      'addCompany',
      'fetchCompany',
      'updateCompany',
      'updateCompanyLogo',
    ]),

    ...mapActions('taxType', ['fetchTaxTypes']),

    ...mapActions('company', ['fetchCompanySettings']),

    ...mapActions('modal', ['openModal']),

    ...mapActions('notification', ['showNotification']),
    
    onUploadHandler(cropper) {
      this.previewLogo = cropper
        .getCroppedCanvas()
        .toDataURL(this.cropperOutputMime)
        
    },
    onHandleUploadError() {
      this.showNotification({
        type: 'error',
        message: 'Oops! Something went wrong...',
      })
    },
    onChange(file) {
      this.cropperOutputMime = file.type
      this.fileObject = file
    },

    currencyNameWithCode({ name, code }) {
      return `${code} - ${name}`
    },

    async loadData() {
      if (this.isEdit) {
        let response = await this.fetchCompany(this.$route.params.id)

        this.formData = { ...response.data.company }

        this.formData.currency = this.currencies.find(
          (currency) => currency.id == response.data.company.currency
        )

        this.previewLogo = response.data.company.logo
        
      } else {
        
      }
    },

    async submitItem() {
      this.$v.formData.$touch()

      if (this.$v.$invalid) {
        return false
      }

      let response
      this.isLoading = true

      if (this.isEdit) {
          response = await this.updateCompany(this.formData)
          if(this.fileObject) {
            let companyData = new FormData();
            companyData.append('company_id', this.$route.params.id)
            companyData.append(
                'company_logo',
                JSON.stringify({
                name: this.fileObject.name,
                data: this.previewLogo,
                })
            )
            
            await this.updateCompanyLogo(companyData)
            }
      } else {
        
        let companyData = new FormData();
        companyData.append('name', this.formData.name)
        companyData.append('domain', this.formData.domain)
        companyData.append('email', this.formData.email)
        companyData.append('address', this.formData.address)
        companyData.append('currency', JSON.stringify(this.formData.currency))
        if(this.fileObject) {
         companyData.append(
            'company_logo',
            JSON.stringify({
              name: this.fileObject.name,
              data: this.previewLogo,
            })
          )   
        }

        response = await this.addCompany(companyData)
        
      }

      if (response.data) {
        this.isLoading = false

        if (!this.isEdit) {
          this.showNotification({
            type: 'success',
            message: this.$tc('companies.created_message'),
          })
          this.$router.push('/admin/companies')
          return true
        } else {
          this.showNotification({
            type: 'success',
            message: this.$tc('companies.updated_message'),
          })
          this.$router.push('/admin/companies')
          return true
        }
        this.showNotification({
          type: 'error',
          message: response.data.error,
        })
      }
    },
  },
}
</script>
