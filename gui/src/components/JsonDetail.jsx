import React from 'react';

export default class JsonDetail extends React.Component {

    constructor(props) {
        super(props);

        this.state = {
            visible: false
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
                <a onClick={() => this.toggle()}>
                    {this.props.title}
                </a>
                {this.state.visible ? this.renderCode() : null}
            </span>
        )
    }

    renderCode() {
        return (
            <p>
                <textarea rows="20" className="form-control">
                    {this.props.code}
                </textarea>
            </p>
        )
    }

}