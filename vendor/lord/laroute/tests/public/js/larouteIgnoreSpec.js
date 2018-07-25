define(['laroute'], function (Laroute) {

    describe('Laroutes generate:laroute', function () {

        it('can ignore routes', function () {
            expect(laroute.route('ignored')).not.toBe('/ignored');
            expect(laroute.route('ignored')).toBe(undefined);
        });

        it('can ignore groups of routes', function () {
            expect(laroute.route('group.ignored')).not.toBe('/ignored');
            expect(laroute.route('group.ignored')).toBe(undefined);
        });

    });

});
