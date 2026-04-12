declare module "vue-filepond/dist/vue-filepond.esm.js" {
  import { DefineComponent } from "vue";
  export function setOptions(options: object): void;
  const vueFilePond: (...plugins: any[]) => DefineComponent<any, any, any>;
  export default vueFilePond;
}
