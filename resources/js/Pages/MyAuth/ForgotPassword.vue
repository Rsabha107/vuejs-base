<script setup>
import { Head, Link } from "@inertiajs/vue3";
import AuthFooter from "@/Components/AuthFooter.vue";
</script>
<template>
  <div class="account-pages my-5 pt-sm-5">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 col-xl-5">
          <div class="card overflow-hidden">
            <div class="bg-primary-subtle">
              <div class="row">
                <div class="col-7">
                  <div class="text-primary p-4">
                    <h5 class="text-primary">Reset Password</h5>
                    <p>Reset Password with Skote.</p>
                  </div>
                </div>
                <div class="col-5 align-self-end">
                  <img
                    alt=""
                    class="img-fluid"
                    src="/assets/images/profile-img.png"
                  />
                </div>
              </div>
            </div>
            <div class="card-body pt-0">
              <div>
                <Link :href="route('mypage')">
                  <div class="avatar-md profile-user-wid mb-4">
                    <span class="avatar-title rounded-circle bg-light">
                      <img
                        alt=""
                        class="rounded-circle"
                        height="34"
                        src="/assets/images/logo.svg"
                      />
                    </span>
                  </div>
                </Link>
              </div>
              <div class="p-2">
                <div class="alert alert-success text-center mb-4" role="alert">
                  Enter your Email and instructions will be sent to you!
                </div>
                <form action="index.html" class="form-horizontal">
                  <div class="mb-3">
                    <label class="form-label" for="useremail"> Email </label>
                    <input
                      class="form-control"
                      id="useremail"
                      placeholder="Enter email"
                      type="email"
                    />
                  </div>
                  <div class="text-end">
                    <button
                      class="btn btn-primary w-md waves-effect waves-light"
                      type="submit"
                    >
                      Reset
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
          <div class="mt-5 text-center">
            <p>
              Remember It ?
              <Link class="fw-medium text-primary" :href="route('mylogin')">
                Sign In here
              </Link>
            </p>
            <AuthFooter />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>