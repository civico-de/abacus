(function (angular) {
  "use strict";

  // SearchKit names a function column after its fields only, so two periods of the same
  // fields collide ("Duplicate alias"); see docs/upstream-bugs.md.
  const periodFunctions = ["IN_PERIOD", "SUM_IN_PERIOD", "COUNT_IN_PERIOD"];
  const aliasArgTypes = ["field", "number", "string"];

  angular
    .module("crmAbacus", CRM.angRequires("crmAbacus"))
    .decorator("crmSearchFunctionDirective", [
      "$delegate",
      ($delegate) => {
        const component = $delegate[0];
        const coreController = component.controller;
        component.controller = [
          "$scope",
          "$injector",
          "searchMeta",
          function ($scope, $injector, searchMeta) {
            $injector.invoke(coreController, this, { $scope });
            const coreWriteExpr = this.writeExpr;
            if (typeof coreWriteExpr !== "function") {
              return;
            }
            this.writeExpr = () => {
              coreWriteExpr();
              const period = this.args[0] && this.args[0].flag_before;
              const as = this.expr ? this.expr.lastIndexOf(" AS ") : -1;
              if (
                this.mode !== "select" ||
                !periodFunctions.includes(this.fnName) ||
                !period ||
                as < 0
              ) {
                return;
              }
              const values = this.args
                .filter((arg) => arg.value && aliasArgTypes.includes(arg.type))
                .map((arg) => String(arg.value));
              const alias = [this.fnName, period, ...values].join("_").replace(/[:.]/g, "_");
              this.expr = this.expr.slice(0, as) + " AS " + searchMeta.createSqlName(alias);
            };
          },
        ];
        return $delegate;
      },
    ]);
})(angular);
