import React from 'react';

export default class JsonDetail extends React.Component {

    static propTypes = {
        code: React.PropTypes.string
    };

    constructor(props) {
        super(props);

        this.state = {
            visible: false // in default code isn't visible
        };
    }

    toggle() {
        this.setState({
            visible: ! this.state.visible
        });
    }

    render() {
        return (
            <span>
                <a href="javascript:;" style={{float: 'right'}} class="btn btn-info" onClick={() => this.toggle()}>
                    json
                </a>
                {this.state.visible ? this.renderCode() : null}
            </span>
        )
    }

    renderCode() {
        return (
            <p>
                <textarea rows="20" className="form-control" defaultValue={this.props.code} />
            </p>
        )
    }

}